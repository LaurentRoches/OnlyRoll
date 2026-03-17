<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Srd\Subclass;
use App\Entity\Srd\Subrace;
use App\Entity\User;
use App\Entity\Wiki\WikiFavorite;
use App\Enum\SrdTable;
use App\Repository\Srd\BackgroundRepository;
use App\Repository\Srd\ClassRepository;
use App\Repository\Srd\FeatRepository;
use App\Repository\Srd\ItemRepository;
use App\Repository\Srd\MonsterRepository;
use App\Repository\Srd\RaceRepository;
use App\Repository\Srd\SpellRepository;
use App\Repository\Srd\SrdTranslationRepository;
use App\Repository\Wiki\WikiArticleRepository;
use App\Repository\Wiki\WikiCategoryRepository;
use App\Repository\Wiki\WikiFavoriteRepository;
use App\Service\Wiki\WikiFavoritesService;
use App\Service\Wiki\WikiSerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/wiki', name: 'api_wiki_')]
final class WikiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SpellRepository $spellRepo,
        private readonly RaceRepository $raceRepo,
        private readonly ClassRepository $classRepo,
        private readonly ItemRepository $itemRepo,
        private readonly MonsterRepository $monsterRepo,
        private readonly BackgroundRepository $bgRepo,
        private readonly FeatRepository $featRepo,
        private readonly WikiArticleRepository $articleRepo,
        private readonly WikiCategoryRepository $categoryRepo,
        private readonly WikiFavoriteRepository $favoriteRepo,
        private readonly SrdTranslationRepository $translationRepo,
        private readonly RateLimiterFactory $wikiSearchLimiter,
        private readonly WikiSerializerService $serializer,
        private readonly WikiFavoritesService $favoritesService,
    ) {}

    private function getAuthenticatedUser(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }

    private function resolveLocale(Request $request): string
    {
        $locale = $request->query->get('locale', 'en');
        return in_array($locale, ['en', 'fr']) ? $locale : 'en';
    }

    /**
     * Bulk-load translations for a list of entity IDs (returns null map for 'en').
     *
     * @return array<int, \App\Entity\Srd\SrdTranslation>|null
     */
    private function loadTranslations(string $srdTable, array $ids, string $locale): ?array
    {
        if ($locale === 'en') {
            return null;
        }
        return $this->translationRepo->findTranslationsForIds($srdTable, $ids, $locale);
    }

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function categories(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $categories = $this->categoryRepo->findBy([], ['sortOrder' => 'ASC']);

        $data = array_map(function ($cat) use ($locale) {
            $translation = $cat->getTranslationForLocale($locale);
            return [
                'slug'  => $cat->getSlug(),
                'icon'  => $cat->getIcon(),
                'name'  => $translation?->getName() ?? $cat->getSlug(),
                'sortOrder' => $cat->getSortOrder(),
            ];
        }, $categories);

        return $this->json($data);
    }

    #[Route('/spells', name: 'spells', methods: ['GET'])]
    public function spells(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $filters = $this->extractFilters($request, ['search', 'level', 'school', 'source', 'class', 'damage_type', 'components']);
        $result = $this->spellRepo->findWithFilters($filters);

        $user = $this->getAuthenticatedUser();
        $favoritedIds = $user ? $this->favoriteRepo->getFavoritedIds($user, 'spell') : [];

        $ids = array_map(fn($s) => $s->getId(), $result['data']);
        $translations = $this->loadTranslations('spell', $ids, $locale);

        return $this->json([
            ...$result,
            'data' => array_map(fn($s) => $this->serializer->serializeSpellList($s, $favoritedIds, $translations[$s->getId()] ?? null), $result['data']),
        ]);
    }

    #[Route('/spells/{id}', name: 'spell_detail', methods: ['GET'])]
    public function spellDetail(int $id, Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $spell = $this->spellRepo->find($id);
        if (!$spell) {
            return $this->json(['error' => 'Spell not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getAuthenticatedUser();
        $isFavorited = $user ? $this->favoriteRepo->isFavorited($user, 'spell', $id) : false;
        $similar = $this->spellRepo->findSimilarSpells($spell, 5);
        $article = $this->articleRepo->findBySrdEntity('spell', $id);

        $translation = $locale !== 'en' ? $this->translationRepo->findTranslation('spell', $id, $locale) : null;

        return $this->json([
            ...$this->serializer->serializeSpellDetail($spell, $translation),
            'isFavorited' => $isFavorited,
            'similarSpells' => array_map(fn($s) => $this->serializer->serializeSpellList($s, []), $similar),
            'wikiContent' => $article?->getTranslationForLocale($locale)?->getContentMarkdown()
                ?? $article?->getTranslationForLocale('en')?->getContentMarkdown(),
        ]);
    }

    #[Route('/races', name: 'races', methods: ['GET'])]
    public function races(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $filters = $this->extractFilters($request, ['search', 'source', 'size', 'speed_type', 'vision']);
        $result = $this->raceRepo->findWithFilters($filters);

        $user = $this->getAuthenticatedUser();
        $favoritedIds = $user ? $this->favoriteRepo->getFavoritedIds($user, 'race') : [];

        $ids = array_map(fn($r) => $r->getId(), $result['data']);
        $translations = $this->loadTranslations('race', $ids, $locale);

        return $this->json([
            ...$result,
            'data' => array_map(fn($r) => $this->serializer->serializeRaceList($r, $favoritedIds, $translations[$r->getId()] ?? null), $result['data']),
        ]);
    }

    #[Route('/races/subraces/{id}', name: 'subrace_detail', methods: ['GET'])]
    public function subraceDetail(int $id, Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $subrace = $this->em->find(Subrace::class, $id);
        if (!$subrace) {
            return $this->json(['error' => 'Subrace not found'], Response::HTTP_NOT_FOUND);
        }

        $translation = $locale !== 'en' ? $this->translationRepo->findTranslation('subrace', $id, $locale) : null;

        return $this->json($this->serializer->serializeSubrace($subrace, $translation));
    }

    #[Route('/races/{id}', name: 'race_detail', methods: ['GET'])]
    public function raceDetail(int $id, Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $race = $this->raceRepo->find($id);
        if (!$race) {
            return $this->json(['error' => 'Race not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getAuthenticatedUser();
        $isFavorited = $user ? $this->favoriteRepo->isFavorited($user, 'race', $id) : false;
        $translation = $locale !== 'en' ? $this->translationRepo->findTranslation('race', $id, $locale) : null;

        return $this->json([
            ...$this->serializer->serializeRaceDetail($race, $translation),
            'isFavorited' => $isFavorited,
        ]);
    }

    #[Route('/classes', name: 'classes', methods: ['GET'])]
    public function classes(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $filters = $this->extractFilters($request, ['search', 'source']);
        $result = $this->classRepo->findWithFilters($filters);

        $user = $this->getAuthenticatedUser();
        $favoritedIds = $user ? $this->favoriteRepo->getFavoritedIds($user, 'class') : [];

        $ids = array_map(fn($c) => $c->getId(), $result['data']);
        $translations = $this->loadTranslations('class', $ids, $locale);

        return $this->json([
            ...$result,
            'data' => array_map(fn($c) => $this->serializer->serializeClassList($c, $favoritedIds, $translations[$c->getId()] ?? null), $result['data']),
        ]);
    }

    #[Route('/classes/subclasses/{id}', name: 'subclass_detail', methods: ['GET'])]
    public function subclassDetail(int $id, Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $subclass = $this->em->find(Subclass::class, $id);
        if (!$subclass) {
            return $this->json(['error' => 'Subclass not found'], Response::HTTP_NOT_FOUND);
        }

        $translation = $locale !== 'en' ? $this->translationRepo->findTranslation('subclass', $id, $locale) : null;

        return $this->json($this->serializer->serializeSubclass($subclass, $translation));
    }

    #[Route('/classes/{id}', name: 'class_detail', methods: ['GET'])]
    public function classDetail(int $id, Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $class = $this->classRepo->find($id);
        if (!$class) {
            return $this->json(['error' => 'Class not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getAuthenticatedUser();
        $isFavorited = $user ? $this->favoriteRepo->isFavorited($user, 'class', $id) : false;
        $translation = $locale !== 'en' ? $this->translationRepo->findTranslation('class', $id, $locale) : null;

        return $this->json([
            ...$this->serializer->serializeClassDetail($class, $translation),
            'isFavorited' => $isFavorited,
        ]);
    }

    #[Route('/items', name: 'items', methods: ['GET'])]
    public function items(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $filters = $this->extractFilters($request, ['search', 'category', 'rarity', 'source']);
        $result = $this->itemRepo->findWithFilters($filters);

        $user = $this->getAuthenticatedUser();
        $favoritedIds = $user ? $this->favoriteRepo->getFavoritedIds($user, 'item') : [];

        $ids = array_map(fn($i) => $i->getId(), $result['data']);
        $translations = $this->loadTranslations('item', $ids, $locale);

        return $this->json([
            ...$result,
            'data' => array_map(fn($i) => $this->serializer->serializeItemList($i, $favoritedIds, $translations[$i->getId()] ?? null), $result['data']),
        ]);
    }

    #[Route('/items/{id}', name: 'item_detail', methods: ['GET'])]
    public function itemDetail(int $id, Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $item = $this->itemRepo->find($id);
        if (!$item) {
            return $this->json(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getAuthenticatedUser();
        $isFavorited = $user ? $this->favoriteRepo->isFavorited($user, 'item', $id) : false;
        $translation = $locale !== 'en' ? $this->translationRepo->findTranslation('item', $id, $locale) : null;

        return $this->json([
            ...$this->serializer->serializeItemDetail($item, $translation),
            'isFavorited' => $isFavorited,
        ]);
    }

    #[Route('/monsters', name: 'monsters', methods: ['GET'])]
    public function monsters(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $filters = $this->extractFilters($request, ['search', 'type', 'size', 'cr', 'source']);
        $result = $this->monsterRepo->findWithFilters($filters);

        $user = $this->getAuthenticatedUser();
        $favoritedIds = $user ? $this->favoriteRepo->getFavoritedIds($user, 'monster') : [];

        $ids = array_map(fn($m) => $m->getId(), $result['data']);
        $translations = $this->loadTranslations('monster', $ids, $locale);

        return $this->json([
            ...$result,
            'data' => array_map(fn($m) => $this->serializer->serializeMonsterList($m, $favoritedIds, $translations[$m->getId()] ?? null), $result['data']),
        ]);
    }

    #[Route('/monsters/{id}', name: 'monster_detail', methods: ['GET'])]
    public function monsterDetail(int $id, Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $monster = $this->monsterRepo->find($id);
        if (!$monster) {
            return $this->json(['error' => 'Monster not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getAuthenticatedUser();
        $isFavorited = $user ? $this->favoriteRepo->isFavorited($user, 'monster', $id) : false;
        $translation = $locale !== 'en' ? $this->translationRepo->findTranslation('monster', $id, $locale) : null;

        return $this->json([
            ...$this->serializer->serializeMonsterDetail($monster, $translation),
            'isFavorited' => $isFavorited,
        ]);
    }

    #[Route('/backgrounds', name: 'backgrounds', methods: ['GET'])]
    public function backgrounds(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $filters = $this->extractFilters($request, ['search', 'source']);
        $result = $this->bgRepo->findWithFilters($filters);

        $user = $this->getAuthenticatedUser();
        $favoritedIds = $user ? $this->favoriteRepo->getFavoritedIds($user, 'background') : [];

        $ids = array_map(fn($b) => $b->getId(), $result['data']);
        $translations = $this->loadTranslations('background', $ids, $locale);

        return $this->json([
            ...$result,
            'data' => array_map(fn($b) => $this->serializer->serializeBackgroundList($b, $favoritedIds, $translations[$b->getId()] ?? null), $result['data']),
        ]);
    }

    #[Route('/backgrounds/{id}', name: 'background_detail', methods: ['GET'])]
    public function backgroundDetail(int $id, Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $bg = $this->bgRepo->find($id);
        if (!$bg) {
            return $this->json(['error' => 'Background not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getAuthenticatedUser();
        $isFavorited = $user ? $this->favoriteRepo->isFavorited($user, 'background', $id) : false;
        $translation = $locale !== 'en' ? $this->translationRepo->findTranslation('background', $id, $locale) : null;

        return $this->json($this->serializer->serializeBackgroundDetail($bg, $isFavorited, $translation));
    }

    #[Route('/feats', name: 'feats', methods: ['GET'])]
    public function feats(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $filters = $this->extractFilters($request, ['search', 'source']);
        $result = $this->featRepo->findWithFilters($filters);

        $user = $this->getAuthenticatedUser();
        $favoritedIds = $user ? $this->favoriteRepo->getFavoritedIds($user, 'feat') : [];

        $ids = array_map(fn($f) => $f->getId(), $result['data']);
        $translations = $this->loadTranslations('feat', $ids, $locale);

        return $this->json([
            ...$result,
            'data' => array_map(fn($f) => $this->serializer->serializeFeatList($f, $favoritedIds, $translations[$f->getId()] ?? null), $result['data']),
        ]);
    }

    #[Route('/feats/{id}', name: 'feat_detail', methods: ['GET'])]
    public function featDetail(int $id, Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $feat = $this->featRepo->find($id);
        if (!$feat) {
            return $this->json(['error' => 'Feat not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getAuthenticatedUser();
        $isFavorited = $user ? $this->favoriteRepo->isFavorited($user, 'feat', $id) : false;
        $translation = $locale !== 'en' ? $this->translationRepo->findTranslation('feat', $id, $locale) : null;

        return $this->json($this->serializer->serializeFeatDetail($feat, $isFavorited, $translation));
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $limiter = $this->wikiSearchLimiter->create($request->getClientIp());
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            return $this->json(['error' => 'Too many search requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $q = trim((string)$request->query->get('q', ''));
        $category = $request->query->get('category');

        if (strlen($q) < 2) {
            return $this->json(['results' => []]);
        }

        $filters = ['search' => $q, 'limit' => 10, 'page' => 1];
        $results = [];

        $searchMap = [
            'spell'      => [$this->spellRepo, 'spell',      fn($s) => ['id' => $s->getId(), 'name' => $s->getName(), 'category' => 'spell',      'meta' => 'Level ' . $s->getLevel()]],
            'race'       => [$this->raceRepo,  'race',       fn($r) => ['id' => $r->getId(), 'name' => $r->getName(), 'category' => 'race',       'meta' => $r->getSources()->first()?->getCode() ?? '']],
            'class'      => [$this->classRepo, 'class',      fn($c) => ['id' => $c->getId(), 'name' => $c->getName(), 'category' => 'class',      'meta' => 'd' . $c->getHitDie()]],
            'background' => [$this->bgRepo,    'background', fn($b) => ['id' => $b->getId(), 'name' => $b->getName(), 'category' => 'background', 'meta' => $b->getSources()->first()?->getCode() ?? '']],
            'feat'       => [$this->featRepo,  'feat',       fn($f) => ['id' => $f->getId(), 'name' => $f->getName(), 'category' => 'feat',       'meta' => $f->getSources()->first()?->getCode() ?? '']],
            'item'       => [$this->itemRepo,  'item',       fn($i) => ['id' => $i->getId(), 'name' => $i->getName(), 'category' => 'item',       'meta' => $i->getRarity()?->getLabel()]],
            'monster'    => [$this->monsterRepo, 'monster',  fn($m) => ['id' => $m->getId(), 'name' => $m->getName(), 'category' => 'monster',    'meta' => 'CR ' . $m->getCr()]],
        ];

        foreach ($searchMap as $cat => [$repo, $srdTable, $serialize]) {
            if ($category && $category !== $cat) {
                continue;
            }
            $res = $repo->findWithFilters($filters);

            $ids = array_map(fn($e) => $e->getId(), $res['data']);
            $translations = $this->loadTranslations($srdTable, $ids, $locale);

            foreach ($res['data'] as $entity) {
                $item = $serialize($entity);
                if ($translations && isset($translations[$entity->getId()])) {
                    $item['name'] = $translations[$entity->getId()]->getField('name') ?? $item['name'];
                }
                $results[] = $item;
            }
        }

        return $this->json(['results' => $results]);
    }

    #[Route('/favorites/items', name: 'favorites_items', methods: ['GET'])]
    public function favoritesItems(): JsonResponse
    {
        $items = $this->favoritesService->getFavoriteItems($this->getAuthenticatedUser());

        return $this->json(['items' => $items]);
    }

    #[Route('/favorites', name: 'favorites_list', methods: ['GET'])]
    public function favoritesList(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $favorites = $this->favoriteRepo->findByUser($user);

        $data = array_map(fn(WikiFavorite $f) => [
            'srdTable'  => $f->getSrdTable(),
            'srdId'     => $f->getSrdId(),
            'createdAt' => $f->getCreatedAt()->format('c'),
        ], $favorites);

        $grouped = [];
        foreach ($data as $fav) {
            $grouped[$fav['srdTable']][] = $fav;
        }

        return $this->json(['favorites' => $grouped]);
    }

    #[Route('/favorites', name: 'favorites_add', methods: ['POST'])]
    public function favoritesAdd(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $srdTable = $data['srdTable'] ?? null;
        $srdId = isset($data['srdId']) ? (int)$data['srdId'] : null;

        if (!$srdTable || !$srdId) {
            return $this->json(['error' => 'Missing srdTable or srdId'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($srdTable, SrdTable::values())) {
            return $this->json(['error' => 'Invalid srdTable'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getAuthenticatedUser();

        if ($this->favoriteRepo->isFavorited($user, $srdTable, $srdId)) {
            return $this->json(['message' => 'Already favorited']);
        }

        $favorite = new WikiFavorite();
        $favorite->setUser($user)->setSrdTable($srdTable)->setSrdId($srdId);
        $this->em->persist($favorite);
        $this->em->flush();

        return $this->json(['message' => 'Favorite added'], Response::HTTP_CREATED);
    }

    #[Route('/favorites/{srdTable}/{srdId}', name: 'favorites_remove', methods: ['DELETE'])]
    public function favoritesRemove(string $srdTable, int $srdId): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $favorite = $this->favoriteRepo->findOne($user, $srdTable, $srdId);

        if (!$favorite) {
            return $this->json(['error' => 'Favorite not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($favorite);
        $this->em->flush();

        return $this->json(['message' => 'Favorite removed']);
    }

    private function extractFilters(Request $request, array $keys): array
    {
        $filters = [];
        foreach ($keys as $key) {
            $val = $request->query->get($key);
            if ($val !== null && $val !== '') {
                $filters[$key] = $val;
            }
        }
        $filters['page']  = (int)$request->query->get('page', 1);
        $filters['limit'] = (int)$request->query->get('limit', 20);
        return $filters;
    }

}

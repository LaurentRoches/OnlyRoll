<?php

declare(strict_types=1);

namespace App\Service\Wiki;

use App\Repository\Srd\BackgroundRepository;
use App\Repository\Srd\ClassRepository;
use App\Repository\Srd\FeatRepository;
use App\Repository\Srd\ItemRepository;
use App\Repository\Srd\MonsterRepository;
use App\Repository\Srd\RaceRepository;
use App\Repository\Srd\SpellRepository;
use App\Repository\Wiki\WikiFavoriteRepository;

final class WikiFavoritesService
{
    public function __construct(
        private readonly WikiFavoriteRepository $favoriteRepo,
        private readonly SpellRepository $spellRepo,
        private readonly RaceRepository $raceRepo,
        private readonly ClassRepository $classRepo,
        private readonly ItemRepository $itemRepo,
        private readonly MonsterRepository $monsterRepo,
        private readonly BackgroundRepository $bgRepo,
        private readonly FeatRepository $featRepo,
    ) {}

    /**
     * Returns all favorited items for the user, merged and sorted by name.
     */
    public function getFavoriteItems(object $user): array
    {
        $favorites = $this->favoriteRepo->findByUser($user);

        $grouped = [];
        foreach ($favorites as $fav) {
            $grouped[$fav->getSrdTable()][] = $fav->getSrdId();
        }

        $items = [];

        foreach ($this->spellRepo->findBy(['id' => $grouped['spell'] ?? []]) as $s) {
            $items[] = ['id' => $s->getId(), 'name' => $s->getName(), 'srdTable' => 'spell', 'source' => $s->getSources()->first()?->getCode() ?? '', 'level' => $s->getLevel(), 'school' => $s->getSchool()?->getLabel()];
        }
        foreach ($this->raceRepo->findBy(['id' => $grouped['race'] ?? []]) as $r) {
            $items[] = ['id' => $r->getId(), 'name' => $r->getName(), 'srdTable' => 'race', 'source' => $r->getSources()->first()?->getCode() ?? '', 'size' => $r->getSize()?->getLabel()];
        }
        foreach ($this->classRepo->findBy(['id' => $grouped['class'] ?? []]) as $c) {
            $items[] = ['id' => $c->getId(), 'name' => $c->getName(), 'srdTable' => 'class', 'source' => $c->getSources()->first()?->getCode() ?? '', 'hitDie' => $c->getHitDie()];
        }
        foreach ($this->itemRepo->findBy(['id' => $grouped['item'] ?? []]) as $i) {
            $items[] = ['id' => $i->getId(), 'name' => $i->getName(), 'srdTable' => 'item', 'source' => $i->getSources()->first()?->getCode() ?? '', 'rarity' => $i->getRarity()?->getLabel()];
        }
        foreach ($this->monsterRepo->findBy(['id' => $grouped['monster'] ?? []]) as $m) {
            $items[] = ['id' => $m->getId(), 'name' => $m->getName(), 'srdTable' => 'monster', 'source' => $m->getSources()->first()?->getCode() ?? '', 'cr' => $m->getCr(), 'type' => $m->getType()?->getLabel()];
        }
        foreach ($this->bgRepo->findBy(['id' => $grouped['background'] ?? []]) as $b) {
            $items[] = ['id' => $b->getId(), 'name' => $b->getName(), 'srdTable' => 'background', 'source' => $b->getSources()->first()?->getCode() ?? ''];
        }
        foreach ($this->featRepo->findBy(['id' => $grouped['feat'] ?? []]) as $f) {
            $items[] = ['id' => $f->getId(), 'name' => $f->getName(), 'srdTable' => 'feat', 'source' => $f->getSources()->first()?->getCode() ?? '', 'prerequisite' => $f->getPrerequisiteText()];
        }

        $names = array_column($items, 'name');
        array_multisort($names, SORT_STRING, $items);

        return $items;
    }
}

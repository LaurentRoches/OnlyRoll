<?php

namespace App\Service\Wiki;

use App\Entity\Wiki\WikiArticle;
use App\Entity\Wiki\WikiArticleTranslation;
use App\Repository\Wiki\WikiArticleRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Syncs SRD entities to wiki_article + wiki_article_translation records.
 * Creates or updates EN translations from SRD data.
 */
class WikiSrdSyncService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WikiArticleRepository $articleRepo,
    ) {}

    /**
     * Sync a single SRD entity.
     *
     * @param string $srdTable  e.g. 'spell', 'race', 'class', etc.
     * @param int    $srdId     The SRD entity ID
     * @param string $title     EN title
     * @param string $content   EN Markdown content
     */
    public function sync(string $srdTable, int $srdId, string $title, string $content): WikiArticle
    {
        $article = $this->articleRepo->findBySrdEntity($srdTable, $srdId);

        if (!$article) {
            $article = new WikiArticle();
            $article->setSrdTable($srdTable)->setSrdId($srdId);
            $this->em->persist($article);
        }

        $translation = $article->getTranslationForLocale('en');
        if (!$translation) {
            $translation = new WikiArticleTranslation();
            $translation->setArticle($article)->setLocale('en');
            $this->em->persist($translation);
        }

        $translation->setTitle($title)->setContentMarkdown($content);

        return $article;
    }

    /**
     * Bulk sync all spells.
     */
    public function syncAll(string $srdTable, array $entities, callable $toTitleContent): void
    {
        foreach ($entities as $entity) {
            [$title, $content] = $toTitleContent($entity);
            $this->sync($srdTable, $entity->getId(), $title, $content);
        }
        $this->em->flush();
    }
}

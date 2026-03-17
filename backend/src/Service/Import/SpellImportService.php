<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Entity\Reference\ContentSource;
use App\Repository\Srd\ClassRepository;
use App\Repository\Srd\SpellRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SpellImportService
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SpellRepository $spellRepo,
        private readonly ClassRepository $classRepo,
        private readonly SpellImportMapper $mapper,
    ) {}

    /**
     * Imports spells from a single JSON file.
     *
     * @param array  $spells      Array of 5etools spell objects
     * @param string $sourceCode  Source book code (e.g. "PHB")
     * @param bool   $isDryRun    If true, no DB writes
     * @param callable|null $onProgress  Called after each spell (for progress bars)
     *
     * @return array{imported: int, updated: int, skipped: int}
     */
    public function importSpells(array $spells, string $sourceCode, bool $isDryRun = false, ?callable $onProgress = null): array
    {
        $source = $this->em->getRepository(ContentSource::class)->findOneBy(['code' => $sourceCode]);
        if (!$source) {
            return ['imported' => 0, 'updated' => 0, 'skipped' => count($spells)];
        }

        $imported = 0;
        $updated  = 0;
        $i        = 0;

        foreach ($spells as $spellData) {
            $name = $spellData['name'] ?? '';
            if (empty($name)) {
                if ($onProgress) {
                    ($onProgress)();
                }
                continue;
            }

            $existing = $this->spellRepo->findByName($name);

            if ($existing !== null) {
                $this->mapper->map($spellData, $existing);
                if (!$existing->hasSource($source)) {
                    $existing->addSource($source);
                }
                $updated++;
            } else {
                $spell = $this->mapper->map($spellData);
                $spell->addSource($source);
                $this->em->persist($spell);
                $imported++;
            }

            if ($onProgress) {
                ($onProgress)();
            }

            $i++;

            if (!$isDryRun && $i % self::BATCH_SIZE === 0) {
                $this->em->flush();
                $this->em->clear();
                $source = $this->em->getRepository(ContentSource::class)->findOneBy(['code' => $sourceCode]);
            }
        }

        if (!$isDryRun) {
            $this->em->flush();
        }

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => 0];
    }

    /**
     * Links spells to classes and damage types from a JSON file.
     *
     * @param array $spells  Array of 5etools spell objects
     */
    public function linkSpellRelations(array $spells): void
    {
        foreach ($spells as $spellData) {
            $name  = $spellData['name'] ?? '';
            $spell = $this->spellRepo->findByName($name);
            if ($spell) {
                $this->mapper->mapClasses($spell, $spellData, $this->classRepo);
                $this->mapper->mapDamageTypes($spell, $spellData);
            }
        }
        $this->em->flush();
    }

    /**
     * Truncates child spell tables before a full import.
     */
    public function truncateChildTables(): void
    {
        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['spell_component', 'spell_class', 'spell_damage_type'] as $table) {
            $conn->executeStatement('TRUNCATE TABLE ' . $table);
        }
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}

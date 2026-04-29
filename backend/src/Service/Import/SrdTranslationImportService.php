<?php

declare(strict_types=1);

namespace App\Service\Import;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Imports translations for SRD entities from 5etools-translated JSON files.
 *
 * Strategy: reads both EN and FR JSON files for the same entity type,
 * matches entries by English name, and stores French translations.
 */
final class SrdTranslationImportService
{
    private const BATCH_SIZE = 50;

    /** Mapping of SRD table names to their repository class short names */
    private const TABLE_REPO_MAP = [
        'spell'      => 'App\Entity\Srd\Spell',
        'race'       => 'App\Entity\Srd\Race',
        'class'      => 'App\Entity\Srd\SrdClass',
        'monster'    => 'App\Entity\Srd\Monster',
        'item'       => 'App\Entity\Srd\Item',
        'background' => 'App\Entity\Srd\Background',
        'feat'       => 'App\Entity\Srd\Feat',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntryParser $entryParser,
        private readonly SpellEntryParser $spellParser,
    ) {}

    /**
     * Import translations for a given entity type.
     *
     * @param string   $srdTable     Entity type ('spell', 'race', etc.)
     * @param array    $frEntries    French JSON entries
     * @param string   $locale       Target locale (e.g. 'fr')
     * @param bool     $isDryRun     If true, no DB writes
     * @param callable|null $onProgress Progress callback
     *
     * @return array{imported: int, updated: int, skipped: int}
     */
    public function importTranslations(
        string $srdTable,
        array $frEntries,
        string $locale,
        bool $isDryRun = false,
        ?callable $onProgress = null,
    ): array {
        $entityClass = self::TABLE_REPO_MAP[$srdTable] ?? null;
        if (!$entityClass) {
            return ['imported' => 0, 'updated' => 0, 'skipped' => count($frEntries)];
        }

        $repo = $this->em->getRepository($entityClass);
        $nameIndex = $this->buildNameIndex($srdTable);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $i = 0;

        foreach ($frEntries as $frEntry) {
            $englishName = $frEntry['_baseName'] ?? $frEntry['_originalName'] ?? null;
            $frenchName = $frEntry['name'] ?? '';

            if (empty($frenchName)) {
                $skipped++;
                if ($onProgress) { ($onProgress)(); }
                continue;
            }

            $entityId = null;
            if ($englishName && isset($nameIndex[$englishName])) {
                $entityId = $nameIndex[$englishName];
            } elseif (isset($nameIndex[$frenchName])) {
                $entityId = $nameIndex[$frenchName];
            }

            if ($entityId === null) {
                $skipped++;
                if ($onProgress) { ($onProgress)(); }
                continue;
            }

            $fields = $this->extractTranslatedFields($srdTable, $frEntry);
            if (empty($fields)) {
                $skipped++;
                if ($onProgress) { ($onProgress)(); }
                continue;
            }

            if (!$isDryRun) {
                $conn = $this->em->getConnection();
                $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
                $jsonFields = json_encode($fields, JSON_UNESCAPED_UNICODE);

                $affected = $conn->executeStatement(
                    'INSERT INTO srd_translation (srd_table, srd_id, locale, translated_fields, created_at, updated_at)
                     VALUES (:table, :id, :locale, :fields, :now, :now)
                     ON DUPLICATE KEY UPDATE translated_fields = :fields, updated_at = :now',
                    ['table' => $srdTable, 'id' => $entityId, 'locale' => $locale, 'fields' => $jsonFields, 'now' => $now]
                );
                if ($affected === 1) {
                    $imported++;
                } else {
                    $updated++;
                }

                $this->importChildTranslations($srdTable, $entityId, $frEntry, $locale);
            } else {
                $imported++;
            }

            if ($onProgress) { ($onProgress)(); }

            $i++;
            if (!$isDryRun && $i % self::BATCH_SIZE === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        if (!$isDryRun) {
            $this->em->flush();
        }

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Build name→ID index for a given SRD table.
     *
     * @return array<string, int>
     */
    private function buildNameIndex(string $srdTable): array
    {
        $tableMap = [
            'spell'      => ['srd_spell', 'spell_id', 'spell_name'],
            'race'       => ['srd_race', 'race_id', 'race_name'],
            'class'      => ['srd_class', 'class_id', 'class_name'],
            'monster'    => ['srd_monster', 'monster_id', 'monster_name'],
            'item'       => ['srd_item', 'item_id', 'item_name'],
            'background' => ['srd_background', 'background_id', 'background_name'],
            'feat'       => ['srd_feat', 'feat_id', 'feat_name'],
        ];

        $config = $tableMap[$srdTable] ?? null;
        if (!$config) {
            return [];
        }

        [$table, $idCol, $nameCol] = $config;
        $rows = $this->em->getConnection()->fetchAllAssociative(
            "SELECT {$idCol} AS id, {$nameCol} AS name FROM {$table}"
        );

        $index = [];
        foreach ($rows as $row) {
            $index[$row['name']] = (int) $row['id'];
        }

        return $index;
    }

    /**
     * Extract translatable fields from a French JSON entry.
     */
    private function extractTranslatedFields(string $srdTable, array $entry): array
    {
        $fields = [];

        if (isset($entry['name'])) {
            $fields['name'] = $entry['name'];
        }

        if (isset($entry['entries'])) {
            $fields['description'] = $this->entryParser->entriesToMarkdown($entry['entries']);
        }

        match ($srdTable) {
            'spell' => $this->extractSpellFields($entry, $fields),
            'background' => $this->extractBackgroundFields($entry, $fields),
            'feat' => $this->extractFeatFields($entry, $fields),
            default => null,
        };

        return $fields;
    }

    private function extractSpellFields(array $entry, array &$fields): void
    {
        if (isset($entry['time'])) {
            $fields['castingTime'] = $this->spellParser->parseCastingTime($entry['time']);
        }
        if (isset($entry['duration'])) {
            $fields['duration'] = $this->spellParser->parseDuration($entry['duration']);
        }
        if (isset($entry['range'])) {
            [$rangeType] = $this->spellParser->parseRange($entry['range']);
            $fields['rangeType'] = $rangeType;
        }
        if (isset($entry['entriesHigherLevel'])) {
            $higherLevel = $this->entryParser->entriesToMarkdown($entry['entriesHigherLevel']);
            if ($higherLevel) {
                $fields['description'] = ($fields['description'] ?? '') . "\n\n**At Higher Levels.** " . $higherLevel;
            }
        }
    }

    private function extractBackgroundFields(array $entry, array &$fields): void
    {
        foreach ($entry['entries'] ?? [] as $block) {
            if (is_array($block) && ($block['type'] ?? '') === 'entries' && isset($block['name'])) {
                $fields['featureName'] = $block['name'];
                if (isset($block['entries'])) {
                    $fields['featureDescription'] = $this->entryParser->entriesToMarkdown($block['entries']);
                }
                break;
            }
        }
    }

    private function extractFeatFields(array $entry, array &$fields): void
    {
        if (isset($entry['prerequisite'])) {
            $prereqs = [];
            foreach ($entry['prerequisite'] as $prereq) {
                if (isset($prereq['race'])) {
                    foreach ($prereq['race'] as $race) {
                        $prereqs[] = $race['name'] ?? '';
                    }
                }
                if (isset($prereq['ability'])) {
                    foreach ($prereq['ability'] as $ability) {
                        foreach ($ability as $stat => $val) {
                            $prereqs[] = strtoupper($stat) . ' ' . $val . '+';
                        }
                    }
                }
                if (isset($prereq['spellcasting'])) {
                    $prereqs[] = 'Spellcasting';
                }
                if (isset($prereq['level'])) {
                    $prereqs[] = 'Level ' . $prereq['level'];
                }
            }
            if (!empty($prereqs)) {
                $fields['prerequisiteText'] = implode(', ', array_filter($prereqs));
            }
        }
    }

    /**
     * Import translations for child entities (traits, actions, features, etc.)
     */
    private function importChildTranslations(string $srdTable, int $parentId, array $frEntry, string $locale): void
    {
        $childMappings = match ($srdTable) {
            'race' => $this->extractRaceChildTranslations($parentId, $frEntry),
            'monster' => $this->extractMonsterChildTranslations($parentId, $frEntry),
            'class' => $this->extractClassChildTranslations($parentId, $frEntry),
            default => [],
        };

        $conn = $this->em->getConnection();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($childMappings as $mapping) {
            $jsonFields = json_encode($mapping['fields'], JSON_UNESCAPED_UNICODE);
            $conn->executeStatement(
                'INSERT INTO srd_child_translation (child_table, child_id, locale, translated_fields, created_at)
                 VALUES (:table, :id, :locale, :fields, :now)
                 ON DUPLICATE KEY UPDATE translated_fields = :fields',
                ['table' => $mapping['childTable'], 'id' => $mapping['childId'], 'locale' => $locale, 'fields' => $jsonFields, 'now' => $now]
            );
        }
    }

    private function extractRaceChildTranslations(int $raceId, array $entry): array
    {
        $mappings = [];

        $traits = $this->em->getConnection()->fetchAllAssociative(
            'SELECT race_trait_id, trait_name FROM race_trait WHERE race_id = ?',
            [$raceId]
        );
        $traitIndex = [];
        foreach ($traits as $t) {
            $traitIndex[$t['trait_name']] = (int) $t['race_trait_id'];
        }

        foreach ($entry['entries'] ?? [] as $block) {
            if (is_array($block) && isset($block['name'], $block['entries'])) {
                $englishTraitName = $block['_baseName'] ?? $block['name'];
                if (isset($traitIndex[$englishTraitName]) || isset($traitIndex[$block['name']])) {
                    $traitId = $traitIndex[$englishTraitName] ?? $traitIndex[$block['name']];
                    $mappings[] = [
                        'childTable' => 'race_trait',
                        'childId' => $traitId,
                        'fields' => [
                            'name' => $block['name'],
                            'description' => $this->entryParser->entriesToMarkdown($block['entries']),
                        ],
                    ];
                }
            }
        }

        return $mappings;
    }

    private function extractMonsterChildTranslations(int $monsterId, array $entry): array
    {
        $mappings = [];

        $mappings = array_merge($mappings, $this->matchChildByName(
            'monster_trait', 'monster_trait_id', 'trait_name', 'monster_id',
            $monsterId, $entry['trait'] ?? []
        ));

        $mappings = array_merge($mappings, $this->matchChildByName(
            'monster_action', 'monster_action_id', 'action_name', 'monster_id',
            $monsterId, $entry['action'] ?? []
        ));

        $mappings = array_merge($mappings, $this->matchChildByName(
            'monster_action', 'monster_action_id', 'action_name', 'monster_id',
            $monsterId, $entry['legendary'] ?? []
        ));

        $mappings = array_merge($mappings, $this->matchChildByName(
            'monster_action', 'monster_action_id', 'action_name', 'monster_id',
            $monsterId, $entry['reaction'] ?? []
        ));

        return $mappings;
    }

    private function extractClassChildTranslations(int $classId, array $entry): array
    {
        return [];
    }

    /**
     * Import class feature translations from the root-level 'classFeature' key.
     * Called separately since these are outside the main 'class' entries.
     */
    public function importClassFeatureTranslations(array $classFeatures, string $locale, bool $isDryRun = false): array
    {
        $classIndex = [];
        $rows = $this->em->getConnection()->fetchAllAssociative(
            'SELECT class_id, class_name FROM srd_class'
        );
        foreach ($rows as $r) {
            $classIndex[$r['class_name']] = (int) $r['class_id'];
        }

        $featureIndex = [];
        $allFeatures = $this->em->getConnection()->fetchAllAssociative(
            'SELECT class_feature_id, feature_name, class_id FROM class_feature'
        );
        foreach ($allFeatures as $f) {
            $featureIndex[(int) $f['class_id']][$f['feature_name']] = (int) $f['class_feature_id'];
        }

        $conn = $this->em->getConnection();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $imported = 0;
        $skipped = 0;

        foreach ($classFeatures as $feature) {
            if (!is_array($feature) || !isset($feature['name'], $feature['className'])) {
                $skipped++;
                continue;
            }

            $className = $feature['className'];
            $classId = $classIndex[$className] ?? null;
            if (!$classId) {
                $skipped++;
                continue;
            }

            $englishName = $feature['_baseName'] ?? $feature['name'];
            $featureId = $featureIndex[$classId][$englishName] ?? $featureIndex[$classId][$feature['name']] ?? null;
            if (!$featureId) {
                $skipped++;
                continue;
            }

            if (!$isDryRun) {
                $fields = ['name' => $feature['name']];
                if (isset($feature['entries'])) {
                    $fields['description'] = $this->entryParser->entriesToMarkdown($feature['entries']);
                }
                $jsonFields = json_encode($fields, JSON_UNESCAPED_UNICODE);

                $conn->executeStatement(
                    'INSERT INTO srd_child_translation (child_table, child_id, locale, translated_fields, created_at)
                     VALUES (:table, :id, :locale, :fields, :now)
                     ON DUPLICATE KEY UPDATE translated_fields = :fields',
                    ['table' => 'class_feature', 'id' => $featureId, 'locale' => $locale, 'fields' => $jsonFields, 'now' => $now]
                );
            }
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Match child entities by name from DB and extract translations.
     */
    private function matchChildByName(
        string $table, string $idCol, string $nameCol, string $parentCol,
        int $parentId, array $entries
    ): array {
        if (empty($entries)) {
            return [];
        }

        $rows = $this->em->getConnection()->fetchAllAssociative(
            "SELECT {$idCol} AS id, {$nameCol} AS name FROM {$table} WHERE {$parentCol} = ?",
            [$parentId]
        );
        $nameIndex = [];
        foreach ($rows as $r) {
            $nameIndex[$r['name']] = (int) $r['id'];
        }

        $mappings = [];
        foreach ($entries as $entry) {
            if (!is_array($entry) || !isset($entry['name'])) {
                continue;
            }
            $englishName = $entry['_baseName'] ?? $entry['name'];
            $childId = $nameIndex[$englishName] ?? $nameIndex[$entry['name']] ?? null;
            if ($childId) {
                $mappings[] = [
                    'childTable' => $table,
                    'childId' => $childId,
                    'fields' => [
                        'name' => $entry['name'],
                        'description' => isset($entry['entries'])
                            ? $this->entryParser->entriesToMarkdown($entry['entries'])
                            : '',
                    ],
                ];
            }
        }

        return $mappings;
    }
}

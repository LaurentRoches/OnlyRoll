<?php

namespace App\Service\Import;

use App\Entity\Reference\CreatureSize;
use App\Entity\Reference\Language;
use App\Entity\Srd\Race;
use App\Entity\Srd\RaceLanguage;
use App\Entity\Srd\RaceSpeed;
use App\Entity\Srd\RaceTrait;
use App\Entity\Srd\Subrace;
use Doctrine\ORM\EntityManagerInterface;

class RaceImportMapper
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntryParser $parser,
    ) {}

    public function map(array $data, ?Race $existing = null): Race
    {
        $race = $existing ?? new Race();

        $race->setName($data['name']);
        $race->setPage(isset($data['page']) ? (int)$data['page'] : null);

        $sizeCode = $data['size'][0] ?? 'M';
        $size = $this->em->getRepository(CreatureSize::class)->findOneBy(['code' => $sizeCode]);
        $race->setSize($size);

        $speed = $data['speed'] ?? 30;
        $race->setWalkSpeed(is_array($speed) ? (int)($speed['walk'] ?? 30) : (int)$speed);

        $abilityMods = [];
        foreach ($data['ability'] ?? [] as $abilitySet) {
            foreach ($abilitySet as $key => $value) {
                if (is_string($key) && is_int($value)) {
                    $abilityMods[$key] = ($abilityMods[$key] ?? 0) + $value;
                }
            }
        }
        $race->setAbilityModifiers($abilityMods ?: null);

        $description = $this->parser->entriesToMarkdown($data['entries'] ?? []);
        $race->setDescription($description ?: null);

        if ($existing) {
            foreach ($existing->getTraits() as $trait) {
                $this->em->remove($trait);
            }
            foreach ($existing->getSpeeds() as $spd) {
                $this->em->remove($spd);
            }
            foreach ($existing->getLanguages() as $lang) {
                $this->em->remove($lang);
            }
        }

        if (is_array($data['speed'] ?? null)) {
            foreach (['fly', 'swim', 'climb', 'burrow'] as $speedType) {
                if (isset($data['speed'][$speedType])) {
                    $rs = new RaceSpeed();
                    $rs->setRace($race)->setSpeedType($speedType)->setSpeedValue((int)$data['speed'][$speedType]);
                    $this->em->persist($rs);
                }
            }
        }

        foreach ($data['entries'] ?? [] as $entry) {
            if (is_array($entry) && isset($entry['name'], $entry['entries'])) {
                $trait = new RaceTrait();
                $trait->setRace($race)
                      ->setName($entry['name'])
                      ->setDescription($this->parser->entriesToMarkdown($entry['entries']));
                $this->em->persist($trait);
            }
        }

        foreach ($data['languageProficiencies'][0] ?? [] as $langSlug => $value) {
            if ($value !== true) continue;
            $slug = strtolower(str_replace(' ', '-', $langSlug));
            $lang = $this->em->getRepository(Language::class)->findOneBy(['slug' => $slug]);
            if (!$lang) continue;
            $rl = new RaceLanguage();
            $rl->setRace($race)->setLanguage($lang);
            $this->em->persist($rl);
        }

        return $race;
    }

    public function mapSubrace(array $data, Race $race, ?Subrace $existing = null): Subrace
    {
        $subrace = $existing ?? new Subrace();

        $subrace->setRace($race);
        $subrace->setName($data['name']);

        $abilityMods = [];
        foreach ($data['ability'] ?? [] as $abilitySet) {
            foreach ($abilitySet as $key => $value) {
                if (is_string($key) && is_int($value)) {
                    $abilityMods[$key] = ($abilityMods[$key] ?? 0) + $value;
                }
            }
        }
        $subrace->setAbilityModifiers($abilityMods ?: null);

        $description = $this->parser->entriesToMarkdown($data['entries'] ?? []);
        $subrace->setDescription($description ?: null);

        return $subrace;
    }
}

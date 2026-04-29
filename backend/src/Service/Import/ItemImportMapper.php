<?php

namespace App\Service\Import;

use App\Entity\Reference\DamageType;
use App\Entity\Reference\ItemCategory;
use App\Entity\Reference\ItemRarity;
use App\Entity\Reference\WeaponProperty;
use App\Entity\Srd\Item;
use App\Entity\Srd\ItemArmor;
use App\Entity\Srd\ItemWeapon;
use App\Entity\Srd\ItemWeaponDamage;
use App\Entity\Srd\ItemWeaponProperty;
use Doctrine\ORM\EntityManagerInterface;

class ItemImportMapper
{
    private const TYPE_MAP = [
        'A'   => 'ammunition',
        'AT'  => 'artisans-tools',
        'EXP' => 'explosive',
        'G'   => 'adventuring-gear',
        'GS'  => 'gaming-set',
        'HA'  => 'heavy-armor',
        'INS' => 'instrument',
        'LA'  => 'light-armor',
        'M'   => 'melee-weapon',
        'MA'  => 'medium-armor',
        'MNT' => 'mount',
        'OTH' => 'other',
        'P'   => 'potion',
        'R'   => 'ranged-weapon',
        'RD'  => 'rod',
        'RG'  => 'ring',
        'S'   => 'shield',
        'SC'  => 'scroll',
        'SCF' => 'spellcasting-focus',
        'SHP' => 'vehicle',
        'ST'  => 'staff',
        'T'   => 'tool',
        'TAH' => 'tack-and-harness',
        'TG'  => 'trade-goods',
        'VEH' => 'vehicle',
        'WD'  => 'wand',
    ];

    private const DAMAGE_TYPE_MAP = [
        'A' => 'acid',
        'B' => 'bludgeoning',
        'C' => 'cold',
        'F' => 'fire',
        'O' => 'force',
        'L' => 'lightning',
        'N' => 'necrotic',
        'P' => 'piercing',
        'I' => 'poison',
        'Y' => 'psychic',
        'R' => 'radiant',
        'S' => 'slashing',
        'T' => 'thunder',
    ];

    private const PROPERTY_MAP = [
        '2H'  => 'two-handed',
        'AF'  => 'ammunition-firearm',
        'BF'  => 'burst-fire',
        'F'   => 'finesse',
        'H'   => 'heavy',
        'L'   => 'light',
        'LD'  => 'loading',
        'R'   => 'reach',
        'RLD' => 'reload',
        'S'   => 'special',
        'T'   => 'thrown',
        'V'   => 'versatile',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntryParser $parser,
    ) {}

    public function map(array $data, ?Item $existing = null): Item
    {
        $item = $existing ?? new Item();

        $item->setName($data['name']);
        $item->setPage(isset($data['page']) ? (int)$data['page'] : null);

        $raritySlug = $data['rarity'] ?? 'none';
        $rarity = $this->em->getRepository(ItemRarity::class)->findOneBy(['slug' => $raritySlug]);
        $item->setRarity($rarity);

        $typeCode = $this->resolveTypeCode($data);
        $categorySlug = self::TYPE_MAP[$typeCode] ?? null;
        if ($categorySlug) {
            $category = $this->em->getRepository(ItemCategory::class)->findOneBy(['slug' => $categorySlug]);
            $item->setCategory($category);
        }

        $isMagical = isset($data['wondrous']) || $this->isMagicRarity($raritySlug);
        $item->setIsMagical($isMagical);

        $item->setRequiresAttunement(isset($data['reqAttune']));
        if (is_string($data['reqAttune'] ?? null)) {
            $item->setAttunementText($data['reqAttune']);
        }

        if (isset($data['weight'])) {
            $item->setWeight((string)$data['weight']);
        }

        if (isset($data['value'])) {
            $item->setValueGp((string)round($data['value'] / 100, 2));
        }

        $entries = $data['entries'] ?? [];
        $item->setDescription($this->parser->entriesToMarkdown($entries));

        if ($existing) {
            foreach ($existing->getWeaponProperties() as $wp) {
                $this->em->remove($wp);
            }
            foreach ($existing->getWeaponDamages() as $wd) {
                $this->em->remove($wd);
            }
        }

        if (isset($data['weapon']) && $data['weapon']) {
            $this->mapWeaponDetails($item, $data, $existing);
        }

        if (in_array($typeCode, ['LA', 'MA', 'HA', 'S'])) {
            $this->mapArmorDetails($item, $data, $typeCode, $existing);
        }

        return $item;
    }

    private function mapWeaponDetails(Item $item, array $data, ?Item $existing): void
    {
        $weapon = ($existing && $existing->getWeapon()) ? $existing->getWeapon() : new ItemWeapon();
        $weapon->setItem($item);

        $weapon->setWeaponCategory($data['weaponCategory'] ?? 'simple');

        if (isset($data['range'])) {
            $parts = explode('/', $data['range']);
            $weapon->setRangeNormal((int) $parts[0]);
            $weapon->setRangeLong(isset($parts[1]) ? (int)$parts[1] : null);
        }

        if (!$existing || !$existing->getWeapon()) {
            $this->em->persist($weapon);
        }

        $dmgTypeCode = $data['dmgType'] ?? null;
        $dmgType = null;
        if ($dmgTypeCode) {
            $dmgTypeSlug = self::DAMAGE_TYPE_MAP[$dmgTypeCode] ?? strtolower($dmgTypeCode);
            $dmgType = $this->em->getRepository(DamageType::class)->findOneBy(['slug' => $dmgTypeSlug]);
        }

        if (isset($data['dmg1']) && $dmgType) {
            preg_match('/(\d+)d(\d+)/', $data['dmg1'], $m);
            if ($m) {
                $dmg = new ItemWeaponDamage();
                $dmg->setItem($item)
                    ->setDiceCnt((int)$m[1])
                    ->setDiceFaces((int)$m[2])
                    ->setDamageType($dmgType)
                    ->setVersatileFormula(null);
                $this->em->persist($dmg);
            }
        }

        if (isset($data['dmg2']) && $dmgType) {
            preg_match('/(\d+)d(\d+)/', $data['dmg2'], $m);
            if ($m) {
                $dmg = new ItemWeaponDamage();
                $dmg->setItem($item)
                    ->setDiceCnt((int)$m[1])
                    ->setDiceFaces((int)$m[2])
                    ->setDamageType($dmgType)
                    ->setVersatileFormula($data['dmg2']);
                $this->em->persist($dmg);
            }
        }

        foreach ($data['property'] ?? [] as $propCode) {
            if (!is_string($propCode)) {
                continue;
            }
            $code = explode('|', $propCode)[0];
            $propSlug = self::PROPERTY_MAP[$code] ?? null;
            if (!$propSlug) {
                continue;
            }
            $prop = $this->em->getRepository(WeaponProperty::class)->findOneBy(['slug' => $propSlug]);
            if (!$prop) {
                continue;
            }
            $wp = new ItemWeaponProperty();
            $wp->setItem($item)->setWeaponProperty($prop);
            $this->em->persist($wp);
        }
    }

    private function mapArmorDetails(Item $item, array $data, string $typeCode, ?Item $existing): void
    {
        $armor = ($existing && $existing->getArmor()) ? $existing->getArmor() : new ItemArmor();
        $armor->setItem($item);

        $armorType = match ($typeCode) {
            'LA' => 'light',
            'MA' => 'medium',
            'HA' => 'heavy',
            'S'  => 'shield',
            default => 'other',
        };
        $armor->setArmorType($armorType);

        $acData = $data['ac'] ?? null;
        if (is_array($acData)) {
            $armor->setArmorClassBase((int)($acData[0] ?? 0));
        } elseif (is_numeric($acData)) {
            $armor->setArmorClassBase((int)$acData);
        }

        $armor->setMaxDexBonus($data['dexterityMax'] ?? null);
        $armor->setStrengthRequirement(isset($data['strength']) ? (int)$data['strength'] : null);
        $armor->setStealthDisadvantage(isset($data['stealth']) && $data['stealth']);

        if (!$existing || !$existing->getArmor()) {
            $this->em->persist($armor);
        }
    }

    private function resolveTypeCode(array $data): string
    {
        $type = $data['type'] ?? null;
        if (is_string($type)) {
            return explode('|', $type)[0];
        }
        if (isset($data['wondrous'])) {
            return 'OTH';
        }
        return 'OTH';
    }

    private function isMagicRarity(string $rarity): bool
    {
        return in_array($rarity, ['common', 'uncommon', 'rare', 'very rare', 'legendary', 'artifact', 'varies', 'unknown (magic)']);
    }
}

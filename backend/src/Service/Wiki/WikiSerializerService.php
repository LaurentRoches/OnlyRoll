<?php

declare(strict_types=1);

namespace App\Service\Wiki;

use App\Entity\Srd\SrdTranslation;

final class WikiSerializerService
{
    /**
     * Overlay a translated value on top of the English value.
     * Returns the translated value if it exists, otherwise the original.
     */
    private function t(?SrdTranslation $translation, string $field, mixed $original): mixed
    {
        if ($translation === null) {
            return $original;
        }
        return $translation->getField($field) ?? $original;
    }

    public function serializeSpellList(object $spell, array $favoritedIds, ?SrdTranslation $translation = null): array
    {
        return [
            'id'              => $spell->getId(),
            'name'            => $this->t($translation, 'name', $spell->getName()),
            'level'           => $spell->getLevel(),
            'school'          => $spell->getSchool()?->getLabel(),
            'schoolSlug'      => $spell->getSchool()?->getSlug(),
            'castingTime'     => $this->t($translation, 'castingTime', $spell->getCastingTime()),
            'range'           => $spell->getRangeType() . ($spell->getRangeDistance() ? ' ' . $spell->getRangeDistance() . ' ft' : ''),
            'isConcentration' => $spell->isConcentration(),
            'isRitual'        => $spell->isRitual(),
            'isFavorited'     => in_array($spell->getId(), $favoritedIds),
        ];
    }

    public function serializeSpellDetail(object $spell, ?SrdTranslation $translation = null): array
    {
        $components = array_map(fn($c) => [
            'type'     => $c->getComponentType(),
            'material' => $c->getMaterialDescription(),
            'cost'     => $c->getMaterialCostGp(),
            'consumed' => $c->isMaterialConsumed(),
        ], $spell->getComponents()->toArray());

        $classes = array_map(fn($sc) => [
            'id'   => $sc->getSrdClass()->getId(),
            'name' => $sc->getSrdClass()->getName(),
        ], $spell->getSpellClasses()->toArray());

        $damageTypes = array_map(fn($sdt) => $sdt->getDamageType()->getLabel(), $spell->getDamageTypes()->toArray());

        return [
            'id'                 => $spell->getId(),
            'name'               => $this->t($translation, 'name', $spell->getName()),
            'level'              => $spell->getLevel(),
            'school'             => $spell->getSchool()?->getLabel(),
            'schoolSlug'         => $spell->getSchool()?->getSlug(),
            'castingTime'        => $this->t($translation, 'castingTime', $spell->getCastingTime()),
            'rangeType'          => $this->t($translation, 'rangeType', $spell->getRangeType()),
            'rangeDistance'      => $spell->getRangeDistance(),
            'duration'           => $this->t($translation, 'duration', $spell->getDuration()),
            'isConcentration'    => $spell->isConcentration(),
            'isRitual'           => $spell->isRitual(),
            'description'        => $this->t($translation, 'description', $spell->getDescription()),
            'damageFormula'      => $spell->getDamageFormula(),
            'upcastDicePerLevel' => $spell->getUpcastDicePerLevel(),
            'upcastDiceFaces'    => $spell->getUpcastDiceFaces(),
            'scalingLevelDice'   => $spell->getScalingLevelDice(),
            'sources'            => array_map(fn($src) => ['code' => $src->getCode(), 'name' => $src->getName()], $spell->getSources()->toArray()),
            'page'               => $spell->getPage(),
            'components'         => $components,
            'classes'            => $classes,
            'damageTypes'        => $damageTypes,
        ];
    }

    public function serializeRaceList(object $race, array $favoritedIds, ?SrdTranslation $translation = null): array
    {
        return [
            'id'          => $race->getId(),
            'name'        => $this->t($translation, 'name', $race->getName()),
            'size'        => $race->getSize()?->getLabel(),
            'sizeSlug'    => $race->getSize()?->getSlug(),
            'walkSpeed'   => $race->getWalkSpeed(),
            'source'      => $race->getSources()->first()?->getCode() ?? '',
            'isFavorited' => in_array($race->getId(), $favoritedIds),
        ];
    }

    public function serializeRaceDetail(object $race, ?SrdTranslation $translation = null): array
    {
        $traits   = array_map(fn($t) => ['id' => $t->getId(), 'name' => $t->getName(), 'description' => $t->getDescription()], $race->getTraits()->toArray());
        $subraces = array_map(fn($sr) => ['id' => $sr->getId(), 'name' => $sr->getName(), 'source' => $sr->getSources()->first()?->getCode() ?? ''], $race->getSubraces()->toArray());
        $speeds   = array_map(fn($s) => ['type' => $s->getSpeedType(), 'value' => $s->getSpeedValue()], $race->getSpeeds()->toArray());

        return [
            'id'               => $race->getId(),
            'name'             => $this->t($translation, 'name', $race->getName()),
            'size'             => $race->getSize()?->getLabel(),
            'sizeSlug'         => $race->getSize()?->getSlug(),
            'walkSpeed'        => $race->getWalkSpeed(),
            'abilityModifiers' => $race->getAbilityModifiers(),
            'description'      => $this->t($translation, 'description', $race->getDescription()),
            'sources'          => array_map(fn($src) => ['code' => $src->getCode(), 'name' => $src->getName()], $race->getSources()->toArray()),
            'source'           => $race->getSources()->first()?->getCode() ?? '',
            'page'             => $race->getPage(),
            'traits'           => $traits,
            'subraces'         => $subraces,
            'speeds'           => $speeds,
        ];
    }

    public function serializeSubrace(object $subrace, ?SrdTranslation $translation = null): array
    {
        return [
            'id'               => $subrace->getId(),
            'name'             => $this->t($translation, 'name', $subrace->getName()),
            'source'           => $subrace->getSources()->first()?->getCode() ?? '',
            'abilityModifiers' => $subrace->getAbilityModifiers(),
            'description'      => $this->t($translation, 'description', $subrace->getDescription()),
        ];
    }

    public function serializeClassList(object $class, array $favoritedIds, ?SrdTranslation $translation = null): array
    {
        return [
            'id'          => $class->getId(),
            'name'        => $this->t($translation, 'name', $class->getName()),
            'hitDie'      => $class->getHitDie(),
            'source'      => $class->getSources()->first()?->getCode() ?? '',
            'isFavorited' => in_array($class->getId(), $favoritedIds),
        ];
    }

    public function serializeClassDetail(object $class, ?SrdTranslation $translation = null): array
    {
        /** @var array<int, array{id: int|null, name: string|null, level: int|null, description: string|null}> $features */
        $features = array_map(fn($f) => [
            'id'          => $f->getId(),
            'name'        => $f->getName(),
            'level'       => $f->getLevel(),
            'description' => $f->getDescription(),
        ], $class->getFeatures()->toArray());

        usort($features, fn(array $a, array $b) => $a['level'] <=> $b['level']);

        $subclasses = array_map(fn($sc) => [
            'id'        => $sc->getId(),
            'name'      => $sc->getName(),
            'shortName' => $sc->getShortName(),
            'source'    => $sc->getSources()->first()?->getCode() ?? '',
        ], $class->getSubclasses()->toArray());

        return [
            'id'                  => $class->getId(),
            'name'                => $this->t($translation, 'name', $class->getName()),
            'hitDie'              => $class->getHitDie(),
            'savingThrows'        => $class->getSavingThrows(),
            'spellcastingAbility' => $class->getSpellcastingAbility(),
            'casterProgression'   => $class->getCasterProgression(),
            'sources'             => array_map(fn($src) => ['code' => $src->getCode(), 'name' => $src->getName()], $class->getSources()->toArray()),
            'source'              => $class->getSources()->first()?->getCode() ?? '',
            'page'                => $class->getPage(),
            'features'            => $features,
            'subclasses'          => $subclasses,
            'proficiencies'       => $class->getProficiencies(),
            'startingEquipment'   => $class->getStartingEquipment(),
            'multiclassing'       => $class->getMulticlassing(),
            'classTableGroups'    => $class->getClassTableGroups(),
        ];
    }

    public function serializeSubclass(object $subclass, ?SrdTranslation $translation = null): array
    {
        $subclassFeatures = array_map(fn($sf) => [
            'id'          => $sf->getId(),
            'name'        => $sf->getName(),
            'level'       => $sf->getLevel(),
            'description' => $sf->getDescription(),
        ], $subclass->getSubclassFeatures()->toArray());

        return [
            'id'               => $subclass->getId(),
            'name'             => $this->t($translation, 'name', $subclass->getName()),
            'shortName'        => $this->t($translation, 'shortName', $subclass->getShortName()),
            'source'           => $subclass->getSources()->first()?->getCode() ?? '',
            'page'             => $subclass->getPage(),
            'description'      => $this->t($translation, 'description', $subclass->getDescription()),
            'subclassFeatures' => $subclassFeatures,
        ];
    }

    public function serializeItemList(object $item, array $favoritedIds, ?SrdTranslation $translation = null): array
    {
        return [
            'id'           => $item->getId(),
            'name'         => $this->t($translation, 'name', $item->getName()),
            'category'     => $item->getCategory()?->getLabel(),
            'categorySlug' => $item->getCategory()?->getSlug(),
            'rarity'       => $item->getRarity()?->getLabel(),
            'raritySlug'   => $item->getRarity()?->getSlug(),
            'isMagical'    => $item->isMagical(),
            'source'       => $item->getSources()->first()?->getCode() ?? '',
            'isFavorited'  => in_array($item->getId(), $favoritedIds),
        ];
    }

    public function serializeItemDetail(object $item, ?SrdTranslation $translation = null): array
    {
        $properties = array_map(fn($p) => $p->getWeaponProperty()->getLabel(), $item->getWeaponProperties()->toArray());
        $damages    = array_map(fn($d) => [
            'dice'       => $d->getDiceCnt() . 'd' . $d->getDiceFaces(),
            'damageType' => $d->getDamageType()->getLabel(),
            'versatile'  => $d->getVersatileFormula(),
        ], $item->getWeaponDamages()->toArray());

        $weapon = $item->getWeapon();
        $armor  = $item->getArmor();

        return [
            'id'                 => $item->getId(),
            'name'               => $this->t($translation, 'name', $item->getName()),
            'category'           => $item->getCategory()?->getLabel(),
            'categorySlug'       => $item->getCategory()?->getSlug(),
            'rarity'             => $item->getRarity()?->getLabel(),
            'raritySlug'         => $item->getRarity()?->getSlug(),
            'isMagical'          => $item->isMagical(),
            'requiresAttunement' => $item->isRequiresAttunement(),
            'attunementText'     => $this->t($translation, 'attunementText', $item->getAttunementText()),
            'weight'             => $item->getWeight(),
            'valueGp'            => $item->getValueGp(),
            'description'        => $this->t($translation, 'description', $item->getDescription()),
            'sources'            => array_map(fn($src) => ['code' => $src->getCode(), 'name' => $src->getName()], $item->getSources()->toArray()),
            'source'             => $item->getSources()->first()?->getCode() ?? '',
            'page'               => $item->getPage(),
            'weaponProperties'   => $properties,
            'weaponDamages'      => $damages,
            'weapon'             => $weapon ? ['category' => $weapon->getWeaponCategory(), 'rangeNormal' => $weapon->getRangeNormal(), 'rangeLong' => $weapon->getRangeLong()] : null,
            'armor'              => $armor ? ['type' => $armor->getArmorType(), 'ac' => $armor->getArmorClassBase(), 'maxDex' => $armor->getMaxDexBonus(), 'strReq' => $armor->getStrengthRequirement(), 'stealthDisadv' => $armor->isStealthDisadvantage()] : null,
        ];
    }

    public function serializeMonsterList(object $monster, array $favoritedIds, ?SrdTranslation $translation = null): array
    {
        return [
            'id'          => $monster->getId(),
            'name'        => $this->t($translation, 'name', $monster->getName()),
            'type'        => $monster->getType()?->getLabel(),
            'typeSlug'    => $monster->getType()?->getSlug(),
            'size'        => $monster->getSize()?->getLabel(),
            'sizeSlug'    => $monster->getSize()?->getSlug(),
            'cr'          => $monster->getCr(),
            'source'      => $monster->getSources()->first()?->getCode() ?? '',
            'isFavorited' => in_array($monster->getId(), $favoritedIds),
        ];
    }

    public function serializeMonsterDetail(object $monster, ?SrdTranslation $translation = null): array
    {
        $actions        = array_map(fn($a) => ['id' => $a->getId(), 'name' => $a->getName(), 'description' => $a->getDescription(), 'isLegendary' => $a->isLegendary(), 'isReaction' => $a->isReaction(), 'isBonus' => $a->isBonus()], $monster->getActions()->toArray());
        $traits         = array_map(fn($t) => ['id' => $t->getId(), 'name' => $t->getName(), 'description' => $t->getDescription()], $monster->getTraits()->toArray());
        $savingThrows   = array_map(fn($s) => ['ability' => $s->getSaveAbility(), 'bonus' => $s->getSaveBonus()], $monster->getSavingThrows()->toArray());
        $skills         = array_map(fn($s) => ['skill' => $s->getSkill()->getLabel(), 'bonus' => $s->getSkillBonus()], $monster->getSkills()->toArray());
        $senses         = array_map(fn($s) => ['type' => $s->getSenseType(), 'range' => $s->getRangeFt()], $monster->getSenses()->toArray());
        $resistances    = array_map(fn($r) => ['damageType' => $r->getDamageType()->getLabel(), 'type' => $r->getResistanceType()], $monster->getDamageResistances()->toArray());
        $condImmunities = array_map(fn($c) => $c->getConditionType()->getLabel(), $monster->getConditionImmunities()->toArray());
        $languages      = array_filter(array_map(fn($l) => $l->getNote() ?? $l->getLanguage()?->getLabel() ?? '', $monster->getLanguages()->toArray()));
        $environments   = array_map(fn($e) => $e->getName(), $monster->getEnvironments()->toArray());

        return [
            'id'                  => $monster->getId(),
            'name'                => $this->t($translation, 'name', $monster->getName()),
            'type'                => $monster->getType()?->getLabel(),
            'typeSlug'            => $monster->getType()?->getSlug(),
            'size'                => $monster->getSize()?->getLabel(),
            'sizeSlug'            => $monster->getSize()?->getSlug(),
            'alignment'           => $monster->getAlignment()?->getLabel(),
            'alignmentSlug'       => $monster->getAlignment()?->getSlug(),
            'armorClass'          => $monster->getArmorClass(),
            'armorDesc'           => $this->t($translation, 'armorDesc', $monster->getArmorDesc()),
            'hitPointsAvg'        => $monster->getHitPointsAvg(),
            'hitDiceFormula'      => $monster->getHitDiceFormula(),
            'walkSpeed'           => $monster->getWalkSpeed(),
            'speeds'              => $monster->getSpeeds(),
            'str'                 => $monster->getStr(),
            'dex'                 => $monster->getDex(),
            'con'                 => $monster->getCon(),
            'int'                 => $monster->getInt(),
            'wis'                 => $monster->getWis(),
            'cha'                 => $monster->getCha(),
            'passivePerception'   => $monster->getPassivePerception(),
            'cr'                  => $monster->getCr(),
            'xp'                  => $monster->getXp(),
            'sources'             => array_map(fn($src) => ['code' => $src->getCode(), 'name' => $src->getName()], $monster->getSources()->toArray()),
            'source'              => $monster->getSources()->first()?->getCode() ?? '',
            'page'                => $monster->getPage(),
            'traits'              => $traits,
            'actions'             => $actions,
            'savingThrows'        => $savingThrows,
            'skills'              => $skills,
            'senses'              => $senses,
            'damageResistances'   => $resistances,
            'conditionImmunities' => $condImmunities,
            'languages'           => array_values($languages),
            'environments'        => $environments,
        ];
    }

    public function serializeBackgroundList(object $bg, array $favoritedIds, ?SrdTranslation $translation = null): array
    {
        return [
            'id'          => $bg->getId(),
            'name'        => $this->t($translation, 'name', $bg->getName()),
            'source'      => $bg->getSources()->first()?->getCode() ?? '',
            'page'        => $bg->getPage(),
            'isFavorited' => in_array($bg->getId(), $favoritedIds),
        ];
    }

    public function serializeBackgroundDetail(object $bg, bool $isFavorited, ?SrdTranslation $translation = null): array
    {
        $skills    = array_map(fn($s) => $s->getSkill()->getLabel(), $bg->getSkills()->toArray());
        $equipment = array_map(fn($e) => ['name' => $e->getItemName(), 'qty' => $e->getQuantity()], $bg->getEquipment()->toArray());

        return [
            'id'                 => $bg->getId(),
            'name'               => $this->t($translation, 'name', $bg->getName()),
            'sources'            => array_map(fn($src) => ['code' => $src->getCode(), 'name' => $src->getName()], $bg->getSources()->toArray()),
            'source'             => $bg->getSources()->first()?->getCode() ?? '',
            'page'               => $bg->getPage(),
            'description'        => $this->t($translation, 'description', $bg->getDescription()),
            'featureName'        => $this->t($translation, 'featureName', $bg->getFeatureName()),
            'featureDescription' => $this->t($translation, 'featureDescription', $bg->getFeatureDescription()),
            'skills'             => $skills,
            'equipment'          => $equipment,
            'isFavorited'        => $isFavorited,
        ];
    }

    public function serializeFeatList(object $feat, array $favoritedIds, ?SrdTranslation $translation = null): array
    {
        return [
            'id'          => $feat->getId(),
            'name'        => $this->t($translation, 'name', $feat->getName()),
            'source'      => $feat->getSources()->first()?->getCode() ?? '',
            'prerequisite'=> $this->t($translation, 'prerequisiteText', $feat->getPrerequisiteText()),
            'isFavorited' => in_array($feat->getId(), $favoritedIds),
        ];
    }

    public function serializeFeatDetail(object $feat, bool $isFavorited, ?SrdTranslation $translation = null): array
    {
        $abilityModifiers = array_map(fn($am) => ['ability' => $am->getAbilityCode(), 'value' => $am->getAbilityValue()], $feat->getAbilityModifiers()->toArray());

        return [
            'id'               => $feat->getId(),
            'name'             => $this->t($translation, 'name', $feat->getName()),
            'sources'          => array_map(fn($src) => ['code' => $src->getCode(), 'name' => $src->getName()], $feat->getSources()->toArray()),
            'source'           => $feat->getSources()->first()?->getCode() ?? '',
            'prerequisite'     => $this->t($translation, 'prerequisiteText', $feat->getPrerequisiteText()),
            'description'      => $this->t($translation, 'description', $feat->getDescription()),
            'abilityModifiers' => $abilityModifiers,
            'isFavorited'      => $isFavorited,
        ];
    }
}

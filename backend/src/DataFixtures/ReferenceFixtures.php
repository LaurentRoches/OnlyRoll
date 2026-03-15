<?php

namespace App\DataFixtures;

use App\Entity\Reference\Alignment;
use App\Entity\Reference\ConditionType;
use App\Entity\Reference\ContentSource;
use App\Entity\Reference\CreatureSize;
use App\Entity\Reference\CreatureType;
use App\Entity\Reference\DamageType;
use App\Entity\Reference\ItemCategory;
use App\Entity\Reference\ItemRarity;
use App\Entity\Reference\Language;
use App\Entity\Reference\Skill;
use App\Entity\Reference\SpellSchool;
use App\Entity\Reference\WeaponProperty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ReferenceFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['reference'];
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadDamageTypes($manager);
        $this->loadWeaponProperties($manager);
        $this->loadItemCategories($manager);
        $this->loadItemRarities($manager);
        $this->loadCreatureSizes($manager);
        $this->loadCreatureTypes($manager);
        $this->loadAlignments($manager);
        $this->loadSpellSchools($manager);
        $this->loadConditionTypes($manager);
        $this->loadSkills($manager);
        $this->loadLanguages($manager);
        $this->loadContentSources($manager);

        $manager->flush();
    }

    private function loadDamageTypes(ObjectManager $manager): void
    {
        $types = [
            ['acid', 'Acid'],
            ['bludgeoning', 'Bludgeoning'],
            ['cold', 'Cold'],
            ['fire', 'Fire'],
            ['force', 'Force'],
            ['lightning', 'Lightning'],
            ['necrotic', 'Necrotic'],
            ['piercing', 'Piercing'],
            ['poison', 'Poison'],
            ['psychic', 'Psychic'],
            ['radiant', 'Radiant'],
            ['slashing', 'Slashing'],
            ['thunder', 'Thunder'],
        ];

        foreach ($types as [$slug, $label]) {
            $entity = new DamageType();
            $entity->setSlug($slug)->setLabel($label);
            $manager->persist($entity);
            $this->addReference('damage_type_' . $slug, $entity);
        }
    }

    private function loadWeaponProperties(ObjectManager $manager): void
    {
        $properties = [
            ['ammunition', 'Ammunition', 'You can use a weapon that has the ammunition property to make a ranged attack only if you have ammunition to fire from the weapon.'],
            ['finesse', 'Finesse', 'When making an attack with a finesse weapon, you use your choice of your Strength or Dexterity modifier for the attack and damage rolls.'],
            ['heavy', 'Heavy', 'Small creatures have disadvantage on attack rolls with heavy weapons.'],
            ['light', 'Light', 'A light weapon is small and easy to handle, making it ideal for use when fighting with two weapons.'],
            ['loading', 'Loading', 'Because of the time required to load this weapon, you can fire only one piece of ammunition from it when you use an action, bonus action, or reaction to fire it.'],
            ['range', 'Range', 'A weapon that can be used to make a ranged attack has a range in parentheses after the ammunition or thrown property.'],
            ['reach', 'Reach', 'This weapon adds 5 feet to your reach when you attack with it.'],
            ['special', 'Special', 'A weapon with the special property has unusual rules governing its use.'],
            ['thrown', 'Thrown', 'If a weapon has the thrown property, you can throw the weapon to make a ranged attack.'],
            ['two-handed', 'Two-Handed', 'This weapon requires two hands when you attack with it.'],
            ['versatile', 'Versatile', 'This weapon can be used with one or two hands.'],
        ];

        foreach ($properties as [$slug, $label, $description]) {
            $entity = new WeaponProperty();
            $entity->setSlug($slug)->setLabel($label)->setDescription($description);
            $manager->persist($entity);
            $this->addReference('weapon_property_' . $slug, $entity);
        }
    }

    private function loadItemCategories(ObjectManager $manager): void
    {
        $categories = [
            ['weapon', 'Weapon', 'W'],
            ['armor', 'Armor', 'A'],
            ['adventuring-gear', 'Adventuring Gear', 'G'],
            ['tool', 'Tool', 'T'],
            ['mount-vehicle', 'Mount & Vehicle', 'V'],
            ['trade-good', 'Trade Good', 'TG'],
            ['treasure', 'Treasure', 'TR'],
            ['wondrous-item', 'Wondrous Item', 'WI'],
            ['potion', 'Potion', 'P'],
            ['ring', 'Ring', 'R'],
            ['rod', 'Rod', 'RD'],
            ['scroll', 'Scroll', 'SC'],
            ['staff', 'Staff', 'ST'],
            ['wand', 'Wand', 'WD'],
            ['ammunition', 'Ammunition', 'AM'],
            ['shield', 'Shield', 'SH'],
        ];

        foreach ($categories as [$slug, $label, $abbr]) {
            $entity = new ItemCategory();
            $entity->setSlug($slug)->setLabel($label)->setAbbreviation($abbr);
            $manager->persist($entity);
            $this->addReference('item_category_' . $slug, $entity);
        }
    }

    private function loadItemRarities(ObjectManager $manager): void
    {
        $rarities = [
            ['none', 'None', 0],
            ['common', 'Common', 1],
            ['uncommon', 'Uncommon', 2],
            ['rare', 'Rare', 3],
            ['very-rare', 'Very Rare', 4],
            ['legendary', 'Legendary', 5],
            ['artifact', 'Artifact', 6],
            ['varies', 'Varies', 7],
        ];

        foreach ($rarities as [$slug, $label, $order]) {
            $entity = new ItemRarity();
            $entity->setSlug($slug)->setLabel($label)->setSortOrder($order);
            $manager->persist($entity);
            $this->addReference('item_rarity_' . $slug, $entity);
        }
    }

    private function loadCreatureSizes(ObjectManager $manager): void
    {
        $sizes = [
            ['tiny', 'Tiny', 'T', 0],
            ['small', 'Small', 'S', 1],
            ['medium', 'Medium', 'M', 2],
            ['large', 'Large', 'L', 3],
            ['huge', 'Huge', 'H', 4],
            ['gargantuan', 'Gargantuan', 'G', 5],
        ];

        foreach ($sizes as [$slug, $label, $code, $order]) {
            $entity = new CreatureSize();
            $entity->setSlug($slug)->setLabel($label)->setCode($code)->setSortOrder($order);
            $manager->persist($entity);
            $this->addReference('creature_size_' . $slug, $entity);
        }
    }

    private function loadCreatureTypes(ObjectManager $manager): void
    {
        $types = [
            'aberration', 'beast', 'celestial', 'construct', 'dragon',
            'elemental', 'fey', 'fiend', 'giant', 'humanoid',
            'monstrosity', 'ooze', 'plant', 'undead',
        ];

        foreach ($types as $slug) {
            $entity = new CreatureType();
            $entity->setSlug($slug)->setLabel(ucfirst($slug));
            $manager->persist($entity);
            $this->addReference('creature_type_' . $slug, $entity);
        }
    }

    private function loadAlignments(ObjectManager $manager): void
    {
        $alignments = [
            ['lawful-good', 'Lawful Good', ['L', 'G']],
            ['neutral-good', 'Neutral Good', ['N', 'G']],
            ['chaotic-good', 'Chaotic Good', ['C', 'G']],
            ['lawful-neutral', 'Lawful Neutral', ['L', 'N']],
            ['true-neutral', 'True Neutral', ['N']],
            ['chaotic-neutral', 'Chaotic Neutral', ['C', 'N']],
            ['lawful-evil', 'Lawful Evil', ['L', 'E']],
            ['neutral-evil', 'Neutral Evil', ['N', 'E']],
            ['chaotic-evil', 'Chaotic Evil', ['C', 'E']],
            ['unaligned', 'Unaligned', ['U']],
            ['any', 'Any Alignment', ['A']],
        ];

        foreach ($alignments as [$slug, $label, $codes]) {
            $entity = new Alignment();
            $entity->setSlug($slug)->setLabel($label)->setCodes($codes);
            $manager->persist($entity);
            $this->addReference('alignment_' . $slug, $entity);
        }
    }

    private function loadSpellSchools(ObjectManager $manager): void
    {
        $schools = [
            ['abjuration', 'Abjuration', 'A'],
            ['conjuration', 'Conjuration', 'C'],
            ['divination', 'Divination', 'D'],
            ['enchantment', 'Enchantment', 'E'],
            ['evocation', 'Evocation', 'V'],
            ['illusion', 'Illusion', 'I'],
            ['necromancy', 'Necromancy', 'N'],
            ['transmutation', 'Transmutation', 'T'],
        ];

        foreach ($schools as [$slug, $label, $code]) {
            $entity = new SpellSchool();
            $entity->setSlug($slug)->setLabel($label)->setCode($code);
            $manager->persist($entity);
            $this->addReference('spell_school_' . $slug, $entity);
        }
    }

    private function loadConditionTypes(ObjectManager $manager): void
    {
        $conditions = [
            ['blinded', 'Blinded'],
            ['charmed', 'Charmed'],
            ['deafened', 'Deafened'],
            ['exhaustion', 'Exhaustion'],
            ['frightened', 'Frightened'],
            ['grappled', 'Grappled'],
            ['incapacitated', 'Incapacitated'],
            ['invisible', 'Invisible'],
            ['paralyzed', 'Paralyzed'],
            ['petrified', 'Petrified'],
            ['poisoned', 'Poisoned'],
            ['prone', 'Prone'],
            ['restrained', 'Restrained'],
            ['stunned', 'Stunned'],
            ['unconscious', 'Unconscious'],
        ];

        foreach ($conditions as [$slug, $label]) {
            $entity = new ConditionType();
            $entity->setSlug($slug)->setLabel($label);
            $manager->persist($entity);
            $this->addReference('condition_type_' . $slug, $entity);
        }
    }

    private function loadSkills(ObjectManager $manager): void
    {
        $skills = [
            ['acrobatics', 'Acrobatics', 'dex'],
            ['animal-handling', 'Animal Handling', 'wis'],
            ['arcana', 'Arcana', 'int'],
            ['athletics', 'Athletics', 'str'],
            ['deception', 'Deception', 'cha'],
            ['history', 'History', 'int'],
            ['insight', 'Insight', 'wis'],
            ['intimidation', 'Intimidation', 'cha'],
            ['investigation', 'Investigation', 'int'],
            ['medicine', 'Medicine', 'wis'],
            ['nature', 'Nature', 'int'],
            ['perception', 'Perception', 'wis'],
            ['performance', 'Performance', 'cha'],
            ['persuasion', 'Persuasion', 'cha'],
            ['religion', 'Religion', 'int'],
            ['sleight-of-hand', 'Sleight of Hand', 'dex'],
            ['stealth', 'Stealth', 'dex'],
            ['survival', 'Survival', 'wis'],
        ];

        foreach ($skills as [$slug, $label, $ability]) {
            $entity = new Skill();
            $entity->setSlug($slug)->setLabel($label)->setAbility($ability);
            $manager->persist($entity);
            $this->addReference('skill_' . $slug, $entity);
        }
    }

    private function loadLanguages(ObjectManager $manager): void
    {
        $languages = [
            ['common', 'Common', 'standard', 'Common'],
            ['dwarvish', 'Dwarvish', 'standard', 'Dwarvish'],
            ['elvish', 'Elvish', 'standard', 'Elvish'],
            ['giant', 'Giant', 'standard', 'Dwarvish'],
            ['gnomish', 'Gnomish', 'standard', 'Dwarvish'],
            ['goblin', 'Goblin', 'standard', 'Dwarvish'],
            ['halfling', 'Halfling', 'standard', 'Common'],
            ['orc', 'Orc', 'standard', 'Dwarvish'],
            ['abyssal', 'Abyssal', 'exotic', 'Infernal'],
            ['celestial', 'Celestial', 'exotic', 'Celestial'],
            ['deep-speech', 'Deep Speech', 'exotic', null],
            ['draconic', 'Draconic', 'exotic', 'Draconic'],
            ['infernal', 'Infernal', 'exotic', 'Infernal'],
            ['primordial', 'Primordial', 'exotic', 'Dwarvish'],
            ['sylvan', 'Sylvan', 'exotic', 'Elvish'],
            ['undercommon', 'Undercommon', 'exotic', 'Elvish'],
            ['druidic', 'Druidic', 'secret', null],
            ['thieves-cant', "Thieves' Cant", 'secret', null],
        ];

        foreach ($languages as [$slug, $label, $type, $script]) {
            $entity = new Language();
            $entity->setSlug($slug)->setLabel($label)->setType($type)->setScript($script);
            $manager->persist($entity);
            $this->addReference('language_' . $slug, $entity);
        }
    }

    private function loadContentSources(ObjectManager $manager): void
    {
        $sources = [
            ['PHB', "Player's Handbook", 'PHB', 2014],
            ['DMG', "Dungeon Master's Guide", 'DMG', 2014],
            ['MM', 'Monster Manual', 'MM', 2014],
            ['SCAG', "Sword Coast Adventurer's Guide", 'SCAG', 2015],
            ['VGM', "Volo's Guide to Monsters", 'VGM', 2016],
            ['XGE', "Xanathar's Guide to Everything", 'XGE', 2017],
            ['MTF', "Mordenkainen's Tome of Foes", 'MTF', 2018],
            ['AI', 'Acquisitions Incorporated', 'AI', 2019],
            ['EGW', "Explorer's Guide to Wildemount", 'EGW', 2020],
            ['MOT', 'Mythic Odysseys of Theros', 'MOT', 2020],
            ['TCE', "Tasha's Cauldron of Everything", 'TCE', 2020],
            ['FTD', 'Fizban\'s Treasury of Dragons', 'FTD', 2021],
            ['SCC', 'Strixhaven: Curriculum of Chaos', 'SCC', 2021],
            ['AAG', 'Astral Adventurer\'s Guide', 'AAG', 2022],
            ['BAM', 'Boo\'s Astral Menagerie', 'BAM', 2022],
            ['ERLW', 'Eberron: Rising from the Last War', 'ERLW', 2019],
            ['GGR', "Guildmasters' Guide to Ravnica", 'GGR', 2018],
            ['BGDIA', 'Baldur\'s Gate: Descent Into Avernus', 'BGDIA', 2019],
            ['XPHB', "Player's Handbook (2024)", 'XPHB', 2024],
            ['XDMG', "Dungeon Master's Guide (2024)", 'XDMG', 2024],
            ['XMM', 'Monster Manual (2025)', 'XMM', 2025],
            ['SRD', 'Systems Reference Document 5.1', 'SRD', 2016],
            ['FREE-RULES', 'Free Rules (2024)', 'FREE-RULES', 2024],
        ];

        foreach ($sources as [$code, $name, $abbr, $year]) {
            $entity = new ContentSource();
            $entity->setCode($code)->setName($name)->setAbbreviation($abbr)->setYear($year);
            $manager->persist($entity);
            $this->addReference('content_source_' . $code, $entity);
        }
    }
}

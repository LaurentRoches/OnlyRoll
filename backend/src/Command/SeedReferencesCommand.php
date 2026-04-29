<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:references',
    description: 'Seed reference tables (content sources, skills, spell schools, etc.) if empty — no FixturesBundle needed',
)]
class SeedReferencesCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $seeded = 0;
        $seeded += $this->seedTable($io, 'content_source', 'content_source_code', [
            ['content_source_code' => 'PHB', 'content_source_name' => "Player's Handbook", 'content_source_abbreviation' => 'PHB', 'content_source_year' => 2014, 'content_source_is_official' => 1],
            ['content_source_code' => 'DMG', 'content_source_name' => "Dungeon Master's Guide", 'content_source_abbreviation' => 'DMG', 'content_source_year' => 2014, 'content_source_is_official' => 1],
            ['content_source_code' => 'MM', 'content_source_name' => 'Monster Manual', 'content_source_abbreviation' => 'MM', 'content_source_year' => 2014, 'content_source_is_official' => 1],
            ['content_source_code' => 'SCAG', 'content_source_name' => "Sword Coast Adventurer's Guide", 'content_source_abbreviation' => 'SCAG', 'content_source_year' => 2015, 'content_source_is_official' => 1],
            ['content_source_code' => 'VGM', 'content_source_name' => "Volo's Guide to Monsters", 'content_source_abbreviation' => 'VGM', 'content_source_year' => 2016, 'content_source_is_official' => 1],
            ['content_source_code' => 'XGE', 'content_source_name' => "Xanathar's Guide to Everything", 'content_source_abbreviation' => 'XGE', 'content_source_year' => 2017, 'content_source_is_official' => 1],
            ['content_source_code' => 'MTF', 'content_source_name' => "Mordenkainen's Tome of Foes", 'content_source_abbreviation' => 'MTF', 'content_source_year' => 2018, 'content_source_is_official' => 1],
            ['content_source_code' => 'AI', 'content_source_name' => 'Acquisitions Incorporated', 'content_source_abbreviation' => 'AI', 'content_source_year' => 2019, 'content_source_is_official' => 1],
            ['content_source_code' => 'EGW', 'content_source_name' => "Explorer's Guide to Wildemount", 'content_source_abbreviation' => 'EGW', 'content_source_year' => 2020, 'content_source_is_official' => 1],
            ['content_source_code' => 'MOT', 'content_source_name' => 'Mythic Odysseys of Theros', 'content_source_abbreviation' => 'MOT', 'content_source_year' => 2020, 'content_source_is_official' => 1],
            ['content_source_code' => 'TCE', 'content_source_name' => "Tasha's Cauldron of Everything", 'content_source_abbreviation' => 'TCE', 'content_source_year' => 2020, 'content_source_is_official' => 1],
            ['content_source_code' => 'FTD', 'content_source_name' => "Fizban's Treasury of Dragons", 'content_source_abbreviation' => 'FTD', 'content_source_year' => 2021, 'content_source_is_official' => 1],
            ['content_source_code' => 'SCC', 'content_source_name' => 'Strixhaven: Curriculum of Chaos', 'content_source_abbreviation' => 'SCC', 'content_source_year' => 2021, 'content_source_is_official' => 1],
            ['content_source_code' => 'AAG', 'content_source_name' => "Astral Adventurer's Guide", 'content_source_abbreviation' => 'AAG', 'content_source_year' => 2022, 'content_source_is_official' => 1],
            ['content_source_code' => 'BAM', 'content_source_name' => "Boo's Astral Menagerie", 'content_source_abbreviation' => 'BAM', 'content_source_year' => 2022, 'content_source_is_official' => 1],
            ['content_source_code' => 'ERLW', 'content_source_name' => 'Eberron: Rising from the Last War', 'content_source_abbreviation' => 'ERLW', 'content_source_year' => 2019, 'content_source_is_official' => 1],
            ['content_source_code' => 'GGR', 'content_source_name' => "Guildmasters' Guide to Ravnica", 'content_source_abbreviation' => 'GGR', 'content_source_year' => 2018, 'content_source_is_official' => 1],
            ['content_source_code' => 'BGDIA', 'content_source_name' => "Baldur's Gate: Descent Into Avernus", 'content_source_abbreviation' => 'BGDIA', 'content_source_year' => 2019, 'content_source_is_official' => 1],
            ['content_source_code' => 'XPHB', 'content_source_name' => "Player's Handbook (2024)", 'content_source_abbreviation' => 'XPHB', 'content_source_year' => 2024, 'content_source_is_official' => 1],
            ['content_source_code' => 'XDMG', 'content_source_name' => "Dungeon Master's Guide (2024)", 'content_source_abbreviation' => 'XDMG', 'content_source_year' => 2024, 'content_source_is_official' => 1],
            ['content_source_code' => 'XMM', 'content_source_name' => 'Monster Manual (2025)', 'content_source_abbreviation' => 'XMM', 'content_source_year' => 2025, 'content_source_is_official' => 1],
            ['content_source_code' => 'SRD', 'content_source_name' => 'Systems Reference Document 5.1', 'content_source_abbreviation' => 'SRD', 'content_source_year' => 2016, 'content_source_is_official' => 1],
            ['content_source_code' => 'FREE-RULES', 'content_source_name' => 'Free Rules (2024)', 'content_source_abbreviation' => 'FREE-RULES', 'content_source_year' => 2024, 'content_source_is_official' => 1],
            ['content_source_code' => 'BMT', 'content_source_name' => "The Book of Many Things", 'content_source_abbreviation' => 'BMT', 'content_source_year' => 2023, 'content_source_is_official' => 1],
            ['content_source_code' => 'DoDk', 'content_source_name' => 'Dungeons of Drakkenheim', 'content_source_abbreviation' => 'DoDk', 'content_source_year' => 2023, 'content_source_is_official' => 0],
            ['content_source_code' => 'GHLoE', 'content_source_name' => "Grim Hollow: Lairs of Etharis", 'content_source_abbreviation' => 'GHLoE', 'content_source_year' => 2022, 'content_source_is_official' => 0],
            ['content_source_code' => 'IDRotF', 'content_source_name' => 'Icewind Dale: Rime of the Frostmaiden', 'content_source_abbreviation' => 'IDRotF', 'content_source_year' => 2020, 'content_source_is_official' => 1],
            ['content_source_code' => 'LLK', 'content_source_name' => 'Lost Laboratory of Kwalish', 'content_source_abbreviation' => 'LLK', 'content_source_year' => 2018, 'content_source_is_official' => 1],
            ['content_source_code' => 'SatO', 'content_source_name' => 'Sigil and the Outlands', 'content_source_abbreviation' => 'SatO', 'content_source_year' => 2024, 'content_source_is_official' => 1],
            ['content_source_code' => 'TDCSR', 'content_source_name' => "Tal'Dorei Campaign Setting Reborn", 'content_source_abbreviation' => 'TDCSR', 'content_source_year' => 2022, 'content_source_is_official' => 0],
        ]);

        $seeded += $this->seedTable($io, 'damage_type', 'damage_type_slug', array_map(
            fn($r) => ['damage_type_slug' => $r[0], 'damage_type_label' => $r[1]],
            [
                ['acid','Acid'],['bludgeoning','Bludgeoning'],['cold','Cold'],['fire','Fire'],
                ['force','Force'],['lightning','Lightning'],['necrotic','Necrotic'],['piercing','Piercing'],
                ['poison','Poison'],['psychic','Psychic'],['radiant','Radiant'],['slashing','Slashing'],['thunder','Thunder'],
            ]
        ));

        $seeded += $this->seedTable($io, 'spell_school', 'spell_school_slug', array_map(
            fn($r) => ['spell_school_slug' => $r[0], 'spell_school_label' => $r[1], 'spell_school_code' => $r[2]],
            [
                ['abjuration','Abjuration','A'],['conjuration','Conjuration','C'],['divination','Divination','D'],
                ['enchantment','Enchantment','E'],['evocation','Evocation','V'],['illusion','Illusion','I'],
                ['necromancy','Necromancy','N'],['transmutation','Transmutation','T'],
            ]
        ));

        $seeded += $this->seedTable($io, 'skill', 'skill_slug', array_map(
            fn($r) => ['skill_slug' => $r[0], 'skill_label' => $r[1], 'skill_ability' => $r[2]],
            [
                ['acrobatics','Acrobatics','dex'],['animal-handling','Animal Handling','wis'],['arcana','Arcana','int'],
                ['athletics','Athletics','str'],['deception','Deception','cha'],['history','History','int'],
                ['insight','Insight','wis'],['intimidation','Intimidation','cha'],['investigation','Investigation','int'],
                ['medicine','Medicine','wis'],['nature','Nature','int'],['perception','Perception','wis'],
                ['performance','Performance','cha'],['persuasion','Persuasion','cha'],['religion','Religion','int'],
                ['sleight-of-hand','Sleight of Hand','dex'],['stealth','Stealth','dex'],['survival','Survival','wis'],
            ]
        ));

        $seeded += $this->seedTable($io, 'condition_type', 'condition_type_slug', array_map(
            fn($r) => ['condition_type_slug' => $r[0], 'condition_type_label' => $r[1]],
            [
                ['blinded','Blinded'],['charmed','Charmed'],['deafened','Deafened'],['exhaustion','Exhaustion'],
                ['frightened','Frightened'],['grappled','Grappled'],['incapacitated','Incapacitated'],['invisible','Invisible'],
                ['paralyzed','Paralyzed'],['petrified','Petrified'],['poisoned','Poisoned'],['prone','Prone'],
                ['restrained','Restrained'],['stunned','Stunned'],['unconscious','Unconscious'],
            ]
        ));

        $seeded += $this->seedTable($io, 'creature_size', 'creature_size_slug', array_map(
            fn($r) => ['creature_size_slug' => $r[0], 'creature_size_label' => $r[1], 'creature_size_code' => $r[2], 'creature_size_sort_order' => $r[3]],
            [['tiny','Tiny','T',0],['small','Small','S',1],['medium','Medium','M',2],['large','Large','L',3],['huge','Huge','H',4],['gargantuan','Gargantuan','G',5]]
        ));

        $seeded += $this->seedTable($io, 'creature_type', 'creature_type_slug', array_map(
            fn($slug) => ['creature_type_slug' => $slug, 'creature_type_label' => ucfirst($slug)],
            ['aberration','beast','celestial','construct','dragon','elemental','fey','fiend','giant','humanoid','monstrosity','ooze','plant','undead']
        ));

        $seeded += $this->seedTable($io, 'alignment', 'alignment_slug', array_map(
            fn($r) => ['alignment_slug' => $r[0], 'alignment_label' => $r[1], 'alignment_codes' => json_encode($r[2])],
            [
                ['lawful-good','Lawful Good',['L','G']],['neutral-good','Neutral Good',['N','G']],['chaotic-good','Chaotic Good',['C','G']],
                ['lawful-neutral','Lawful Neutral',['L','N']],['true-neutral','True Neutral',['N']],['chaotic-neutral','Chaotic Neutral',['C','N']],
                ['lawful-evil','Lawful Evil',['L','E']],['neutral-evil','Neutral Evil',['N','E']],['chaotic-evil','Chaotic Evil',['C','E']],
                ['unaligned','Unaligned',['U']],['any','Any Alignment',['A']],
            ]
        ));

        $seeded += $this->seedTable($io, 'weapon_property', 'weapon_property_slug', array_map(
            fn($r) => ['weapon_property_slug' => $r[0], 'weapon_property_label' => $r[1], 'weapon_property_description' => $r[2]],
            [
                ['ammunition','Ammunition','You can use a weapon that has the ammunition property to make a ranged attack only if you have ammunition to fire from the weapon.'],
                ['finesse','Finesse','When making an attack with a finesse weapon, you use your choice of your Strength or Dexterity modifier for the attack and damage rolls.'],
                ['heavy','Heavy','Small creatures have disadvantage on attack rolls with heavy weapons.'],
                ['light','Light','A light weapon is small and easy to handle, making it ideal for use when fighting with two weapons.'],
                ['loading','Loading','Because of the time required to load this weapon, you can fire only one piece of ammunition from it when you use an action, bonus action, or reaction to fire it.'],
                ['range','Range','A weapon that can be used to make a ranged attack has a range in parentheses after the ammunition or thrown property.'],
                ['reach','Reach','This weapon adds 5 feet to your reach when you attack with it.'],
                ['special','Special','A weapon with the special property has unusual rules governing its use.'],
                ['thrown','Thrown','If a weapon has the thrown property, you can throw the weapon to make a ranged attack.'],
                ['two-handed','Two-Handed','This weapon requires two hands when you attack with it.'],
                ['versatile','Versatile','This weapon can be used with one or two hands.'],
            ]
        ));

        $seeded += $this->seedTable($io, 'item_category', 'item_category_slug', array_map(
            fn($r) => ['item_category_slug' => $r[0], 'item_category_label' => $r[1], 'category_abbreviation' => $r[2]],
            [
                ['weapon','Weapon','W'],['armor','Armor','A'],['adventuring-gear','Adventuring Gear','G'],
                ['tool','Tool','T'],['mount-vehicle','Mount & Vehicle','V'],['trade-good','Trade Good','TG'],
                ['treasure','Treasure','TR'],['wondrous-item','Wondrous Item','WI'],['potion','Potion','P'],
                ['ring','Ring','R'],['rod','Rod','RD'],['scroll','Scroll','SC'],['staff','Staff','ST'],
                ['wand','Wand','WD'],['ammunition','Ammunition','AM'],['shield','Shield','SH'],
            ]
        ));

        $seeded += $this->seedTable($io, 'item_rarity', 'item_rarity_slug', array_map(
            fn($r) => ['item_rarity_slug' => $r[0], 'item_rarity_label' => $r[1], 'item_rarity_sort_order' => $r[2]],
            [['none','None',0],['common','Common',1],['uncommon','Uncommon',2],['rare','Rare',3],['very-rare','Very Rare',4],['legendary','Legendary',5],['artifact','Artifact',6],['varies','Varies',7]]
        ));

        $seeded += $this->seedTable($io, 'language', 'language_slug', array_map(
            fn($r) => ['language_slug' => $r[0], 'language_label' => $r[1], 'language_type' => $r[2], 'language_script' => $r[3]],
            [
                ['common','Common','standard','Common'],['dwarvish','Dwarvish','standard','Dwarvish'],
                ['elvish','Elvish','standard','Elvish'],['giant','Giant','standard','Dwarvish'],
                ['gnomish','Gnomish','standard','Dwarvish'],['goblin','Goblin','standard','Dwarvish'],
                ['halfling','Halfling','standard','Common'],['orc','Orc','standard','Dwarvish'],
                ['abyssal','Abyssal','exotic','Infernal'],['celestial','Celestial','exotic','Celestial'],
                ['deep-speech','Deep Speech','exotic',null],['draconic','Draconic','exotic','Draconic'],
                ['infernal','Infernal','exotic','Infernal'],['primordial','Primordial','exotic','Dwarvish'],
                ['sylvan','Sylvan','exotic','Elvish'],['undercommon','Undercommon','exotic','Elvish'],
                ['druidic','Druidic','secret',null],['thieves-cant',"Thieves' Cant",'secret',null],
            ]
        ));

        if ($seeded === 0) {
            $io->info('All reference tables already populated, nothing to do.');
        } else {
            $io->success("Seeded $seeded rows across reference tables.");
        }

        return Command::SUCCESS;
    }

    private function seedTable(SymfonyStyle $io, string $table, string $uniqueColumn, array $rows): int
    {
        $count = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            $io->text("  $table: already has $count rows, skipping.");
            return 0;
        }

        $inserted = 0;
        foreach ($rows as $row) {
            $this->connection->insert($table, $row);
            $inserted++;
        }

        $io->text("  $table: inserted $inserted rows.");
        return $inserted;
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates all SRD, Reference, and Wiki tables.
 */
final class Version20260305070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create SRD, Reference and Wiki tables (schema without data)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE alignment (alignment_id INT AUTO_INCREMENT NOT NULL, alignment_slug VARCHAR(50) NOT NULL, alignment_label VARCHAR(100) NOT NULL, alignment_codes JSON DEFAULT NULL, UNIQUE INDEX UNIQ_2CCE1E5C2A9D39FB (alignment_slug), PRIMARY KEY(alignment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE condition_type (condition_type_id INT AUTO_INCREMENT NOT NULL, condition_type_slug VARCHAR(50) NOT NULL, condition_type_label VARCHAR(100) NOT NULL, condition_type_description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_3E2A02082BDEB078 (condition_type_slug), PRIMARY KEY(condition_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE content_source (content_source_id INT AUTO_INCREMENT NOT NULL, content_source_code VARCHAR(20) NOT NULL, content_source_name VARCHAR(200) NOT NULL, content_source_abbreviation VARCHAR(20) DEFAULT NULL, content_source_year SMALLINT DEFAULT NULL, content_source_is_official TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_37BC0FDF25B51F1D (content_source_code), PRIMARY KEY(content_source_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE creature_size (creature_size_id INT AUTO_INCREMENT NOT NULL, creature_size_slug VARCHAR(20) NOT NULL, creature_size_label VARCHAR(50) NOT NULL, creature_size_code VARCHAR(2) NOT NULL, creature_size_sort_order SMALLINT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_6D6C4BE782C70824 (creature_size_slug), UNIQUE INDEX UNIQ_6D6C4BE76D4FA3DE (creature_size_code), PRIMARY KEY(creature_size_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE creature_type (creature_type_id INT AUTO_INCREMENT NOT NULL, creature_type_slug VARCHAR(50) NOT NULL, creature_type_label VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_167238A43E6D0B88 (creature_type_slug), PRIMARY KEY(creature_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE damage_type (damage_type_id INT AUTO_INCREMENT NOT NULL, damage_type_slug VARCHAR(50) NOT NULL, damage_type_label VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_533C1638E81C00B (damage_type_slug), PRIMARY KEY(damage_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE item_category (item_category_id INT AUTO_INCREMENT NOT NULL, item_category_slug VARCHAR(50) NOT NULL, item_category_label VARCHAR(100) NOT NULL, category_abbreviation VARCHAR(10) DEFAULT NULL, UNIQUE INDEX UNIQ_6A41D10AFFAA49FE (item_category_slug), PRIMARY KEY(item_category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE item_rarity (item_rarity_id INT AUTO_INCREMENT NOT NULL, item_rarity_slug VARCHAR(50) NOT NULL, item_rarity_label VARCHAR(100) NOT NULL, item_rarity_sort_order SMALLINT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_3139CB8935E58930 (item_rarity_slug), PRIMARY KEY(item_rarity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE language (language_id INT AUTO_INCREMENT NOT NULL, language_slug VARCHAR(50) NOT NULL, language_label VARCHAR(100) NOT NULL, language_type VARCHAR(20) DEFAULT NULL, language_script VARCHAR(50) DEFAULT NULL, UNIQUE INDEX UNIQ_D4DB71B5AA94712E (language_slug), PRIMARY KEY(language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE skill (skill_id INT AUTO_INCREMENT NOT NULL, skill_slug VARCHAR(50) NOT NULL, skill_label VARCHAR(100) NOT NULL, skill_ability VARCHAR(3) NOT NULL, UNIQUE INDEX UNIQ_5E3DE477DFDC07D0 (skill_slug), PRIMARY KEY(skill_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE spell_school (spell_school_id INT AUTO_INCREMENT NOT NULL, spell_school_slug VARCHAR(50) NOT NULL, spell_school_label VARCHAR(100) NOT NULL, spell_school_code VARCHAR(2) NOT NULL, UNIQUE INDEX UNIQ_413F2DAE9EA26184 (spell_school_slug), UNIQUE INDEX UNIQ_413F2DAE712ACA7E (spell_school_code), PRIMARY KEY(spell_school_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weapon_property (weapon_property_id INT AUTO_INCREMENT NOT NULL, weapon_property_slug VARCHAR(50) NOT NULL, weapon_property_label VARCHAR(100) NOT NULL, weapon_property_description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_FAE6AE17F9F06BF8 (weapon_property_slug), PRIMARY KEY(weapon_property_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE srd_background (background_id INT AUTO_INCREMENT NOT NULL, background_source_id INT NOT NULL, background_name VARCHAR(200) NOT NULL, background_description LONGTEXT DEFAULT NULL, background_feature_name VARCHAR(200) DEFAULT NULL, background_feature_description LONGTEXT DEFAULT NULL, background_page SMALLINT DEFAULT NULL, INDEX IDX_2ED842590E7347F (background_source_id), PRIMARY KEY(background_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE srd_class (class_id INT AUTO_INCREMENT NOT NULL, class_source_id INT NOT NULL, class_name VARCHAR(100) NOT NULL, class_hit_die SMALLINT NOT NULL, class_saving_throws JSON NOT NULL, class_spellcasting_ability VARCHAR(3) DEFAULT NULL, class_caster_progression VARCHAR(20) DEFAULT NULL, class_page SMALLINT DEFAULT NULL, INDEX IDX_681A4105FEE3835E (class_source_id), PRIMARY KEY(class_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE srd_feat (feat_id INT AUTO_INCREMENT NOT NULL, feat_source_id INT NOT NULL, feat_name VARCHAR(200) NOT NULL, feat_prerequisite_text LONGTEXT DEFAULT NULL, feat_description LONGTEXT DEFAULT NULL, feat_page SMALLINT DEFAULT NULL, INDEX IDX_6EF3D399705E0009 (feat_source_id), PRIMARY KEY(feat_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE srd_item (item_id INT AUTO_INCREMENT NOT NULL, item_source_id INT NOT NULL, item_category_id INT DEFAULT NULL, item_rarity_id INT DEFAULT NULL, item_name VARCHAR(200) NOT NULL, item_weight NUMERIC(8, 2) DEFAULT NULL, item_value_gp NUMERIC(12, 2) DEFAULT NULL, item_is_magical TINYINT(1) DEFAULT 0 NOT NULL, item_requires_attunement TINYINT(1) DEFAULT 0 NOT NULL, item_attunement_text VARCHAR(200) DEFAULT NULL, item_description LONGTEXT DEFAULT NULL, item_page SMALLINT DEFAULT NULL, INDEX IDX_2B73674C99E60A91 (item_source_id), INDEX IDX_2B73674CF22EC5D4 (item_category_id), INDEX IDX_2B73674CFFAE6383 (item_rarity_id), PRIMARY KEY(item_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE srd_monster (monster_id INT AUTO_INCREMENT NOT NULL, monster_source_id INT NOT NULL, monster_size_id INT DEFAULT NULL, monster_type_id INT DEFAULT NULL, monster_alignment_id INT DEFAULT NULL, monster_name VARCHAR(200) NOT NULL, monster_armor_class SMALLINT DEFAULT NULL, monster_armor_desc VARCHAR(200) DEFAULT NULL, monster_hit_points_avg SMALLINT DEFAULT NULL, monster_hit_dice_formula VARCHAR(20) DEFAULT NULL, monster_walk_speed SMALLINT DEFAULT NULL, monster_speeds JSON DEFAULT NULL, monster_str SMALLINT DEFAULT NULL, monster_dex SMALLINT DEFAULT NULL, monster_con SMALLINT DEFAULT NULL, monster_int SMALLINT DEFAULT NULL, monster_wis SMALLINT DEFAULT NULL, monster_cha SMALLINT DEFAULT NULL, monster_passive_perception SMALLINT DEFAULT NULL, monster_cr VARCHAR(10) DEFAULT NULL, monster_xp INT DEFAULT NULL, monster_page SMALLINT DEFAULT NULL, INDEX IDX_CA40F8F371841726 (monster_source_id), INDEX IDX_CA40F8F3EBEC1918 (monster_size_id), INDEX IDX_CA40F8F3672D3DAC (monster_type_id), INDEX IDX_CA40F8F3F8E1BB74 (monster_alignment_id), PRIMARY KEY(monster_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE srd_race (race_id INT AUTO_INCREMENT NOT NULL, race_source_id INT NOT NULL, race_size_id INT DEFAULT NULL, race_name VARCHAR(100) NOT NULL, race_walk_speed SMALLINT DEFAULT 30 NOT NULL, race_ability_modifiers JSON DEFAULT NULL, race_description LONGTEXT DEFAULT NULL, race_page SMALLINT DEFAULT NULL, INDEX IDX_EE07F9FD4CBA5C7C (race_source_id), INDEX IDX_EE07F9FD563D7D57 (race_size_id), PRIMARY KEY(race_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE srd_spell (spell_id INT AUTO_INCREMENT NOT NULL, spell_source_id INT NOT NULL, spell_school_id INT NOT NULL, spell_name VARCHAR(200) NOT NULL, spell_level SMALLINT NOT NULL, spell_casting_time VARCHAR(100) NOT NULL, spell_range_type VARCHAR(50) NOT NULL, spell_range_distance INT DEFAULT NULL, spell_duration VARCHAR(200) NOT NULL, spell_is_concentration TINYINT(1) DEFAULT 0 NOT NULL, spell_is_ritual TINYINT(1) DEFAULT 0 NOT NULL, spell_description LONGTEXT DEFAULT NULL, spell_damage_formula VARCHAR(50) DEFAULT NULL, spell_upcast_dice_per_level SMALLINT DEFAULT NULL, spell_upcast_dice_faces SMALLINT DEFAULT NULL, spell_scaling_level_dice JSON DEFAULT NULL, spell_page SMALLINT DEFAULT NULL, INDEX IDX_556E951775FD9946 (spell_source_id), INDEX IDX_556E951723EBC2C9 (spell_school_id), PRIMARY KEY(spell_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE srd_subclass (subclass_id INT AUTO_INCREMENT NOT NULL, class_id INT NOT NULL, subclass_source_id INT NOT NULL, subclass_name VARCHAR(200) NOT NULL, subclass_short_name VARCHAR(100) DEFAULT NULL, subclass_page SMALLINT DEFAULT NULL, INDEX IDX_9727DD8EA000B10 (class_id), INDEX IDX_9727DD8433E8CC5 (subclass_source_id), PRIMARY KEY(subclass_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE srd_subrace (subrace_id INT AUTO_INCREMENT NOT NULL, race_id INT NOT NULL, subrace_source_id INT NOT NULL, subrace_name VARCHAR(200) NOT NULL, subrace_ability_modifiers JSON DEFAULT NULL, subrace_description LONGTEXT DEFAULT NULL, INDEX IDX_EDC4F7236E59D40D (race_id), INDEX IDX_EDC4F7232C47DB8C (subrace_source_id), PRIMARY KEY(subrace_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE background_equipment (background_equipment_id INT AUTO_INCREMENT NOT NULL, background_id INT NOT NULL, equipment_item_name VARCHAR(200) NOT NULL, equipment_quantity SMALLINT DEFAULT 1 NOT NULL, INDEX IDX_D6E3FD9DC93D69EA (background_id), PRIMARY KEY(background_equipment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE background_language (background_language_id INT AUTO_INCREMENT NOT NULL, background_id INT NOT NULL, language_count SMALLINT DEFAULT 0 NOT NULL, language_choices JSON DEFAULT NULL, INDEX IDX_FA47C72C93D69EA (background_id), PRIMARY KEY(background_language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE background_skill (background_skill_id INT AUTO_INCREMENT NOT NULL, background_id INT NOT NULL, skill_id INT NOT NULL, INDEX IDX_F1A7650C93D69EA (background_id), INDEX IDX_F1A76505585C142 (skill_id), PRIMARY KEY(background_skill_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE class_feature (class_feature_id INT AUTO_INCREMENT NOT NULL, class_id INT NOT NULL, feature_name VARCHAR(200) NOT NULL, feature_level SMALLINT NOT NULL, feature_description LONGTEXT DEFAULT NULL, INDEX IDX_DAA2129AEA000B10 (class_id), PRIMARY KEY(class_feature_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE feat_ability_modifier (feat_ability_modifier_id INT AUTO_INCREMENT NOT NULL, feat_id INT NOT NULL, ability_code VARCHAR(3) NOT NULL, ability_value SMALLINT NOT NULL, INDEX IDX_7BDCB431F43C4D5C (feat_id), PRIMARY KEY(feat_ability_modifier_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE feat_benefit (feat_benefit_id INT AUTO_INCREMENT NOT NULL, feat_id INT NOT NULL, benefit_type VARCHAR(30) NOT NULL, benefit_description LONGTEXT NOT NULL, INDEX IDX_43B2F661F43C4D5C (feat_id), PRIMARY KEY(feat_benefit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE feat_prerequisite (feat_prerequisite_id INT AUTO_INCREMENT NOT NULL, feat_id INT NOT NULL, prerequisite_type VARCHAR(30) NOT NULL, prerequisite_value VARCHAR(200) NOT NULL, INDEX IDX_469B656CF43C4D5C (feat_id), PRIMARY KEY(feat_prerequisite_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE item_armor (item_armor_id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, armor_class_base SMALLINT NOT NULL, armor_max_dex_bonus SMALLINT DEFAULT NULL, armor_strength_requirement SMALLINT DEFAULT NULL, armor_stealth_disadvantage TINYINT(1) DEFAULT 0 NOT NULL, armor_type VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_9580E52C126F525E (item_id), PRIMARY KEY(item_armor_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE item_weapon (item_weapon_id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, weapon_category VARCHAR(20) NOT NULL, weapon_range_normal SMALLINT DEFAULT NULL, weapon_range_long SMALLINT DEFAULT NULL, UNIQUE INDEX UNIQ_EFCAD229126F525E (item_id), PRIMARY KEY(item_weapon_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE item_weapon_damage (item_weapon_damage_id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, damage_type_id INT NOT NULL, damage_dice_count SMALLINT NOT NULL, damage_dice_faces SMALLINT NOT NULL, damage_versatile_formula VARCHAR(20) DEFAULT NULL, INDEX IDX_C091D390126F525E (item_id), INDEX IDX_C091D39041E13755 (damage_type_id), PRIMARY KEY(item_weapon_damage_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE item_weapon_property (item_weapon_property_id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, weapon_property_id INT NOT NULL, INDEX IDX_402775B5126F525E (item_id), INDEX IDX_402775B56C5A615 (weapon_property_id), PRIMARY KEY(item_weapon_property_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE monster_action (monster_action_id INT AUTO_INCREMENT NOT NULL, monster_id INT NOT NULL, action_name VARCHAR(200) NOT NULL, action_description LONGTEXT DEFAULT NULL, action_is_legendary TINYINT(1) DEFAULT 0 NOT NULL, action_is_reaction TINYINT(1) DEFAULT 0 NOT NULL, action_is_bonus TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_410CC1B1C5FF1223 (monster_id), PRIMARY KEY(monster_action_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE monster_condition_immunity (monster_condition_immunity_id INT AUTO_INCREMENT NOT NULL, monster_id INT NOT NULL, condition_type_id INT NOT NULL, INDEX IDX_655AC7C0C5FF1223 (monster_id), INDEX IDX_655AC7C081DF3FE2 (condition_type_id), PRIMARY KEY(monster_condition_immunity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE monster_damage_resistance (monster_damage_resistance_id INT AUTO_INCREMENT NOT NULL, monster_id INT NOT NULL, damage_type_id INT NOT NULL, resistance_type VARCHAR(20) NOT NULL, INDEX IDX_38EAA9BBC5FF1223 (monster_id), INDEX IDX_38EAA9BB41E13755 (damage_type_id), PRIMARY KEY(monster_damage_resistance_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE monster_environment (monster_environment_id INT AUTO_INCREMENT NOT NULL, monster_id INT NOT NULL, environment_name VARCHAR(50) NOT NULL, INDEX IDX_6ECC3AD0C5FF1223 (monster_id), PRIMARY KEY(monster_environment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE monster_language (monster_language_id INT AUTO_INCREMENT NOT NULL, monster_id INT NOT NULL, language_id INT DEFAULT NULL, language_note VARCHAR(200) DEFAULT NULL, language_can_speak TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_621F3D39C5FF1223 (monster_id), INDEX IDX_621F3D3982F1BAF4 (language_id), PRIMARY KEY(monster_language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE monster_saving_throw (monster_saving_throw_id INT AUTO_INCREMENT NOT NULL, monster_id INT NOT NULL, save_ability VARCHAR(3) NOT NULL, save_bonus SMALLINT NOT NULL, INDEX IDX_1E4EAB8DC5FF1223 (monster_id), PRIMARY KEY(monster_saving_throw_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE monster_sense (monster_sense_id INT AUTO_INCREMENT NOT NULL, monster_id INT NOT NULL, sense_type VARCHAR(50) NOT NULL, sense_range_ft SMALLINT NOT NULL, INDEX IDX_79D30FBEC5FF1223 (monster_id), PRIMARY KEY(monster_sense_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE monster_skill (monster_skill_id INT AUTO_INCREMENT NOT NULL, monster_id INT NOT NULL, skill_id INT NOT NULL, skill_bonus SMALLINT NOT NULL, INDEX IDX_28C5D832C5FF1223 (monster_id), INDEX IDX_28C5D8325585C142 (skill_id), PRIMARY KEY(monster_skill_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE monster_subtype (monster_subtype_id INT AUTO_INCREMENT NOT NULL, monster_id INT NOT NULL, subtype_name VARCHAR(100) NOT NULL, INDEX IDX_F70A949DC5FF1223 (monster_id), PRIMARY KEY(monster_subtype_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE monster_trait (monster_trait_id INT AUTO_INCREMENT NOT NULL, monster_id INT NOT NULL, trait_name VARCHAR(200) NOT NULL, trait_description LONGTEXT DEFAULT NULL, INDEX IDX_D7FC219CC5FF1223 (monster_id), PRIMARY KEY(monster_trait_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE race_language (race_language_id INT AUTO_INCREMENT NOT NULL, race_id INT NOT NULL, language_id INT NOT NULL, INDEX IDX_84C1B05C6E59D40D (race_id), INDEX IDX_84C1B05C82F1BAF4 (language_id), PRIMARY KEY(race_language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE race_speed (race_speed_id INT AUTO_INCREMENT NOT NULL, race_id INT NOT NULL, speed_type VARCHAR(20) NOT NULL, speed_value SMALLINT NOT NULL, INDEX IDX_E918DFE76E59D40D (race_id), PRIMARY KEY(race_speed_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE race_trait (race_trait_id INT AUTO_INCREMENT NOT NULL, race_id INT NOT NULL, trait_name VARCHAR(200) NOT NULL, trait_description LONGTEXT DEFAULT NULL, INDEX IDX_473A3CC86E59D40D (race_id), PRIMARY KEY(race_trait_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE spell_class (spell_class_id INT AUTO_INCREMENT NOT NULL, spell_id INT NOT NULL, class_id INT NOT NULL, INDEX IDX_F11903A8479EC90D (spell_id), INDEX IDX_F11903A8EA000B10 (class_id), PRIMARY KEY(spell_class_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE spell_component (spell_component_id INT AUTO_INCREMENT NOT NULL, spell_id INT NOT NULL, component_type VARCHAR(1) NOT NULL, component_material_description LONGTEXT DEFAULT NULL, component_material_cost_gp INT DEFAULT NULL, component_material_consumed TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_A93F2470479EC90D (spell_id), PRIMARY KEY(spell_component_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE spell_damage_type (spell_damage_type_id INT AUTO_INCREMENT NOT NULL, spell_id INT NOT NULL, damage_type_id INT NOT NULL, INDEX IDX_4224E468479EC90D (spell_id), INDEX IDX_4224E46841E13755 (damage_type_id), PRIMARY KEY(spell_damage_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE wiki_article (wiki_article_id INT AUTO_INCREMENT NOT NULL, srd_table VARCHAR(30) NOT NULL, srd_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(wiki_article_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE wiki_article_translation (wiki_article_translation_id INT AUTO_INCREMENT NOT NULL, wiki_article_id INT NOT NULL, locale VARCHAR(5) NOT NULL, title VARCHAR(300) NOT NULL, content_markdown LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_EFF57F0F4E9C5254 (wiki_article_id), PRIMARY KEY(wiki_article_translation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE wiki_category (wiki_category_id INT AUTO_INCREMENT NOT NULL, wiki_category_slug VARCHAR(50) NOT NULL, wiki_category_icon VARCHAR(50) DEFAULT NULL, wiki_category_sort_order SMALLINT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_ED8A1C0E6735AEC8 (wiki_category_slug), PRIMARY KEY(wiki_category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE wiki_category_translation (wiki_category_translation_id INT AUTO_INCREMENT NOT NULL, wiki_category_id INT NOT NULL, locale VARCHAR(5) NOT NULL, name VARCHAR(100) NOT NULL, INDEX IDX_9D578DF787C5DFB4 (wiki_category_id), PRIMARY KEY(wiki_category_translation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE wiki_favorite (wiki_favorite_id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, srd_table VARCHAR(30) NOT NULL, srd_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_83038B16A76ED395 (user_id), PRIMARY KEY(wiki_favorite_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE wiki_favorite ADD CONSTRAINT FK_83038B16A76ED395 FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wiki_article_translation ADD CONSTRAINT FK_EFF57F0F4E9C5254 FOREIGN KEY (wiki_article_id) REFERENCES wiki_article (wiki_article_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wiki_category_translation ADD CONSTRAINT FK_9D578DF787C5DFB4 FOREIGN KEY (wiki_category_id) REFERENCES wiki_category (wiki_category_id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE srd_background ADD CONSTRAINT FK_2ED842590E7347F FOREIGN KEY (background_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_class ADD CONSTRAINT FK_681A4105FEE3835E FOREIGN KEY (class_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_feat ADD CONSTRAINT FK_6EF3D399705E0009 FOREIGN KEY (feat_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_item ADD CONSTRAINT FK_2B73674C99E60A91 FOREIGN KEY (item_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_item ADD CONSTRAINT FK_2B73674CF22EC5D4 FOREIGN KEY (item_category_id) REFERENCES item_category (item_category_id)');
        $this->addSql('ALTER TABLE srd_item ADD CONSTRAINT FK_2B73674CFFAE6383 FOREIGN KEY (item_rarity_id) REFERENCES item_rarity (item_rarity_id)');
        $this->addSql('ALTER TABLE srd_monster ADD CONSTRAINT FK_CA40F8F371841726 FOREIGN KEY (monster_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_monster ADD CONSTRAINT FK_CA40F8F3EBEC1918 FOREIGN KEY (monster_size_id) REFERENCES creature_size (creature_size_id)');
        $this->addSql('ALTER TABLE srd_monster ADD CONSTRAINT FK_CA40F8F3672D3DAC FOREIGN KEY (monster_type_id) REFERENCES creature_type (creature_type_id)');
        $this->addSql('ALTER TABLE srd_monster ADD CONSTRAINT FK_CA40F8F3F8E1BB74 FOREIGN KEY (monster_alignment_id) REFERENCES alignment (alignment_id)');
        $this->addSql('ALTER TABLE srd_race ADD CONSTRAINT FK_EE07F9FD4CBA5C7C FOREIGN KEY (race_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_race ADD CONSTRAINT FK_EE07F9FD563D7D57 FOREIGN KEY (race_size_id) REFERENCES creature_size (creature_size_id)');
        $this->addSql('ALTER TABLE srd_spell ADD CONSTRAINT FK_556E951775FD9946 FOREIGN KEY (spell_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_spell ADD CONSTRAINT FK_556E951723EBC2C9 FOREIGN KEY (spell_school_id) REFERENCES spell_school (spell_school_id)');
        $this->addSql('ALTER TABLE srd_subclass ADD CONSTRAINT FK_9727DD8EA000B10 FOREIGN KEY (class_id) REFERENCES srd_class (class_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE srd_subclass ADD CONSTRAINT FK_9727DD8433E8CC5 FOREIGN KEY (subclass_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_subrace ADD CONSTRAINT FK_EDC4F7236E59D40D FOREIGN KEY (race_id) REFERENCES srd_race (race_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE srd_subrace ADD CONSTRAINT FK_EDC4F7232C47DB8C FOREIGN KEY (subrace_source_id) REFERENCES content_source (content_source_id)');

        $this->addSql('ALTER TABLE background_equipment ADD CONSTRAINT FK_D6E3FD9DC93D69EA FOREIGN KEY (background_id) REFERENCES srd_background (background_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE background_language ADD CONSTRAINT FK_FA47C72C93D69EA FOREIGN KEY (background_id) REFERENCES srd_background (background_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE background_skill ADD CONSTRAINT FK_F1A7650C93D69EA FOREIGN KEY (background_id) REFERENCES srd_background (background_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE background_skill ADD CONSTRAINT FK_F1A76505585C142 FOREIGN KEY (skill_id) REFERENCES skill (skill_id)');
        $this->addSql('ALTER TABLE class_feature ADD CONSTRAINT FK_DAA2129AEA000B10 FOREIGN KEY (class_id) REFERENCES srd_class (class_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feat_ability_modifier ADD CONSTRAINT FK_7BDCB431F43C4D5C FOREIGN KEY (feat_id) REFERENCES srd_feat (feat_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feat_benefit ADD CONSTRAINT FK_43B2F661F43C4D5C FOREIGN KEY (feat_id) REFERENCES srd_feat (feat_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feat_prerequisite ADD CONSTRAINT FK_469B656CF43C4D5C FOREIGN KEY (feat_id) REFERENCES srd_feat (feat_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_armor ADD CONSTRAINT FK_9580E52C126F525E FOREIGN KEY (item_id) REFERENCES srd_item (item_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_weapon ADD CONSTRAINT FK_EFCAD229126F525E FOREIGN KEY (item_id) REFERENCES srd_item (item_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_weapon_damage ADD CONSTRAINT FK_C091D390126F525E FOREIGN KEY (item_id) REFERENCES srd_item (item_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_weapon_damage ADD CONSTRAINT FK_C091D39041E13755 FOREIGN KEY (damage_type_id) REFERENCES damage_type (damage_type_id)');
        $this->addSql('ALTER TABLE item_weapon_property ADD CONSTRAINT FK_402775B5126F525E FOREIGN KEY (item_id) REFERENCES srd_item (item_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_weapon_property ADD CONSTRAINT FK_402775B56C5A615 FOREIGN KEY (weapon_property_id) REFERENCES weapon_property (weapon_property_id)');
        $this->addSql('ALTER TABLE monster_action ADD CONSTRAINT FK_410CC1B1C5FF1223 FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE monster_condition_immunity ADD CONSTRAINT FK_655AC7C0C5FF1223 FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE monster_condition_immunity ADD CONSTRAINT FK_655AC7C081DF3FE2 FOREIGN KEY (condition_type_id) REFERENCES condition_type (condition_type_id)');
        $this->addSql('ALTER TABLE monster_damage_resistance ADD CONSTRAINT FK_38EAA9BBC5FF1223 FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE monster_damage_resistance ADD CONSTRAINT FK_38EAA9BB41E13755 FOREIGN KEY (damage_type_id) REFERENCES damage_type (damage_type_id)');
        $this->addSql('ALTER TABLE monster_environment ADD CONSTRAINT FK_6ECC3AD0C5FF1223 FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE monster_language ADD CONSTRAINT FK_621F3D39C5FF1223 FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE monster_language ADD CONSTRAINT FK_621F3D3982F1BAF4 FOREIGN KEY (language_id) REFERENCES language (language_id)');
        $this->addSql('ALTER TABLE monster_saving_throw ADD CONSTRAINT FK_1E4EAB8DC5FF1223 FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE monster_sense ADD CONSTRAINT FK_79D30FBEC5FF1223 FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE monster_skill ADD CONSTRAINT FK_28C5D832C5FF1223 FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE monster_skill ADD CONSTRAINT FK_28C5D8325585C142 FOREIGN KEY (skill_id) REFERENCES skill (skill_id)');
        $this->addSql('ALTER TABLE monster_subtype ADD CONSTRAINT FK_F70A949DC5FF1223 FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE monster_trait ADD CONSTRAINT FK_D7FC219CC5FF1223 FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE race_language ADD CONSTRAINT FK_84C1B05C6E59D40D FOREIGN KEY (race_id) REFERENCES srd_race (race_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE race_language ADD CONSTRAINT FK_84C1B05C82F1BAF4 FOREIGN KEY (language_id) REFERENCES language (language_id)');
        $this->addSql('ALTER TABLE race_speed ADD CONSTRAINT FK_E918DFE76E59D40D FOREIGN KEY (race_id) REFERENCES srd_race (race_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE race_trait ADD CONSTRAINT FK_473A3CC86E59D40D FOREIGN KEY (race_id) REFERENCES srd_race (race_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE spell_class ADD CONSTRAINT FK_F11903A8479EC90D FOREIGN KEY (spell_id) REFERENCES srd_spell (spell_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE spell_class ADD CONSTRAINT FK_F11903A8EA000B10 FOREIGN KEY (class_id) REFERENCES srd_class (class_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE spell_component ADD CONSTRAINT FK_A93F2470479EC90D FOREIGN KEY (spell_id) REFERENCES srd_spell (spell_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE spell_damage_type ADD CONSTRAINT FK_4224E468479EC90D FOREIGN KEY (spell_id) REFERENCES srd_spell (spell_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE spell_damage_type ADD CONSTRAINT FK_4224E46841E13755 FOREIGN KEY (damage_type_id) REFERENCES damage_type (damage_type_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wiki_favorite DROP FOREIGN KEY FK_83038B16A76ED395');
        $this->addSql('ALTER TABLE wiki_article_translation DROP FOREIGN KEY FK_EFF57F0F4E9C5254');
        $this->addSql('ALTER TABLE wiki_category_translation DROP FOREIGN KEY FK_9D578DF787C5DFB4');
        $this->addSql('ALTER TABLE spell_damage_type DROP FOREIGN KEY FK_4224E46841E13755');
        $this->addSql('ALTER TABLE spell_damage_type DROP FOREIGN KEY FK_4224E468479EC90D');
        $this->addSql('ALTER TABLE spell_component DROP FOREIGN KEY FK_A93F2470479EC90D');
        $this->addSql('ALTER TABLE spell_class DROP FOREIGN KEY FK_F11903A8EA000B10');
        $this->addSql('ALTER TABLE spell_class DROP FOREIGN KEY FK_F11903A8479EC90D');
        $this->addSql('ALTER TABLE race_trait DROP FOREIGN KEY FK_473A3CC86E59D40D');
        $this->addSql('ALTER TABLE race_speed DROP FOREIGN KEY FK_E918DFE76E59D40D');
        $this->addSql('ALTER TABLE race_language DROP FOREIGN KEY FK_84C1B05C82F1BAF4');
        $this->addSql('ALTER TABLE race_language DROP FOREIGN KEY FK_84C1B05C6E59D40D');
        $this->addSql('ALTER TABLE monster_trait DROP FOREIGN KEY FK_D7FC219CC5FF1223');
        $this->addSql('ALTER TABLE monster_subtype DROP FOREIGN KEY FK_F70A949DC5FF1223');
        $this->addSql('ALTER TABLE monster_skill DROP FOREIGN KEY FK_28C5D8325585C142');
        $this->addSql('ALTER TABLE monster_skill DROP FOREIGN KEY FK_28C5D832C5FF1223');
        $this->addSql('ALTER TABLE monster_sense DROP FOREIGN KEY FK_79D30FBEC5FF1223');
        $this->addSql('ALTER TABLE monster_saving_throw DROP FOREIGN KEY FK_1E4EAB8DC5FF1223');
        $this->addSql('ALTER TABLE monster_language DROP FOREIGN KEY FK_621F3D3982F1BAF4');
        $this->addSql('ALTER TABLE monster_language DROP FOREIGN KEY FK_621F3D39C5FF1223');
        $this->addSql('ALTER TABLE monster_environment DROP FOREIGN KEY FK_6ECC3AD0C5FF1223');
        $this->addSql('ALTER TABLE monster_damage_resistance DROP FOREIGN KEY FK_38EAA9BB41E13755');
        $this->addSql('ALTER TABLE monster_damage_resistance DROP FOREIGN KEY FK_38EAA9BBC5FF1223');
        $this->addSql('ALTER TABLE monster_condition_immunity DROP FOREIGN KEY FK_655AC7C081DF3FE2');
        $this->addSql('ALTER TABLE monster_condition_immunity DROP FOREIGN KEY FK_655AC7C0C5FF1223');
        $this->addSql('ALTER TABLE monster_action DROP FOREIGN KEY FK_410CC1B1C5FF1223');
        $this->addSql('ALTER TABLE item_weapon_property DROP FOREIGN KEY FK_402775B56C5A615');
        $this->addSql('ALTER TABLE item_weapon_property DROP FOREIGN KEY FK_402775B5126F525E');
        $this->addSql('ALTER TABLE item_weapon_damage DROP FOREIGN KEY FK_C091D39041E13755');
        $this->addSql('ALTER TABLE item_weapon_damage DROP FOREIGN KEY FK_C091D390126F525E');
        $this->addSql('ALTER TABLE item_weapon DROP FOREIGN KEY FK_EFCAD229126F525E');
        $this->addSql('ALTER TABLE item_armor DROP FOREIGN KEY FK_9580E52C126F525E');
        $this->addSql('ALTER TABLE feat_prerequisite DROP FOREIGN KEY FK_469B656CF43C4D5C');
        $this->addSql('ALTER TABLE feat_benefit DROP FOREIGN KEY FK_43B2F661F43C4D5C');
        $this->addSql('ALTER TABLE feat_ability_modifier DROP FOREIGN KEY FK_7BDCB431F43C4D5C');
        $this->addSql('ALTER TABLE class_feature DROP FOREIGN KEY FK_DAA2129AEA000B10');
        $this->addSql('ALTER TABLE background_skill DROP FOREIGN KEY FK_F1A76505585C142');
        $this->addSql('ALTER TABLE background_skill DROP FOREIGN KEY FK_F1A7650C93D69EA');
        $this->addSql('ALTER TABLE background_language DROP FOREIGN KEY FK_FA47C72C93D69EA');
        $this->addSql('ALTER TABLE background_equipment DROP FOREIGN KEY FK_D6E3FD9DC93D69EA');
        $this->addSql('ALTER TABLE srd_subrace DROP FOREIGN KEY FK_EDC4F7232C47DB8C');
        $this->addSql('ALTER TABLE srd_subrace DROP FOREIGN KEY FK_EDC4F7236E59D40D');
        $this->addSql('ALTER TABLE srd_subclass DROP FOREIGN KEY FK_9727DD8433E8CC5');
        $this->addSql('ALTER TABLE srd_subclass DROP FOREIGN KEY FK_9727DD8EA000B10');
        $this->addSql('ALTER TABLE srd_spell DROP FOREIGN KEY FK_556E951723EBC2C9');
        $this->addSql('ALTER TABLE srd_spell DROP FOREIGN KEY FK_556E951775FD9946');
        $this->addSql('ALTER TABLE srd_race DROP FOREIGN KEY FK_EE07F9FD563D7D57');
        $this->addSql('ALTER TABLE srd_race DROP FOREIGN KEY FK_EE07F9FD4CBA5C7C');
        $this->addSql('ALTER TABLE srd_monster DROP FOREIGN KEY FK_CA40F8F3F8E1BB74');
        $this->addSql('ALTER TABLE srd_monster DROP FOREIGN KEY FK_CA40F8F3672D3DAC');
        $this->addSql('ALTER TABLE srd_monster DROP FOREIGN KEY FK_CA40F8F3EBEC1918');
        $this->addSql('ALTER TABLE srd_monster DROP FOREIGN KEY FK_CA40F8F371841726');
        $this->addSql('ALTER TABLE srd_item DROP FOREIGN KEY FK_2B73674CFFAE6383');
        $this->addSql('ALTER TABLE srd_item DROP FOREIGN KEY FK_2B73674CF22EC5D4');
        $this->addSql('ALTER TABLE srd_item DROP FOREIGN KEY FK_2B73674C99E60A91');
        $this->addSql('ALTER TABLE srd_feat DROP FOREIGN KEY FK_6EF3D399705E0009');
        $this->addSql('ALTER TABLE srd_class DROP FOREIGN KEY FK_681A4105FEE3835E');
        $this->addSql('ALTER TABLE srd_background DROP FOREIGN KEY FK_2ED842590E7347F');

        $this->addSql('DROP TABLE spell_damage_type');
        $this->addSql('DROP TABLE spell_component');
        $this->addSql('DROP TABLE spell_class');
        $this->addSql('DROP TABLE race_trait');
        $this->addSql('DROP TABLE race_speed');
        $this->addSql('DROP TABLE race_language');
        $this->addSql('DROP TABLE monster_trait');
        $this->addSql('DROP TABLE monster_subtype');
        $this->addSql('DROP TABLE monster_skill');
        $this->addSql('DROP TABLE monster_sense');
        $this->addSql('DROP TABLE monster_saving_throw');
        $this->addSql('DROP TABLE monster_language');
        $this->addSql('DROP TABLE monster_environment');
        $this->addSql('DROP TABLE monster_damage_resistance');
        $this->addSql('DROP TABLE monster_condition_immunity');
        $this->addSql('DROP TABLE monster_action');
        $this->addSql('DROP TABLE item_weapon_property');
        $this->addSql('DROP TABLE item_weapon_damage');
        $this->addSql('DROP TABLE item_weapon');
        $this->addSql('DROP TABLE item_armor');
        $this->addSql('DROP TABLE feat_prerequisite');
        $this->addSql('DROP TABLE feat_benefit');
        $this->addSql('DROP TABLE feat_ability_modifier');
        $this->addSql('DROP TABLE class_feature');
        $this->addSql('DROP TABLE background_skill');
        $this->addSql('DROP TABLE background_language');
        $this->addSql('DROP TABLE background_equipment');
        $this->addSql('DROP TABLE srd_subrace');
        $this->addSql('DROP TABLE srd_subclass');
        $this->addSql('DROP TABLE srd_spell');
        $this->addSql('DROP TABLE srd_race');
        $this->addSql('DROP TABLE srd_monster');
        $this->addSql('DROP TABLE srd_item');
        $this->addSql('DROP TABLE srd_feat');
        $this->addSql('DROP TABLE srd_class');
        $this->addSql('DROP TABLE srd_background');
        $this->addSql('DROP TABLE wiki_favorite');
        $this->addSql('DROP TABLE wiki_category_translation');
        $this->addSql('DROP TABLE wiki_category');
        $this->addSql('DROP TABLE wiki_article_translation');
        $this->addSql('DROP TABLE wiki_article');
        $this->addSql('DROP TABLE weapon_property');
        $this->addSql('DROP TABLE spell_school');
        $this->addSql('DROP TABLE skill');
        $this->addSql('DROP TABLE language');
        $this->addSql('DROP TABLE item_rarity');
        $this->addSql('DROP TABLE item_category');
        $this->addSql('DROP TABLE damage_type');
        $this->addSql('DROP TABLE creature_type');
        $this->addSql('DROP TABLE creature_size');
        $this->addSql('DROP TABLE content_source');
        $this->addSql('DROP TABLE condition_type');
        $this->addSql('DROP TABLE alignment');
    }
}

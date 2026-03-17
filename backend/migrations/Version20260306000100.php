<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refactoring : passage de ManyToOne vers ManyToMany pour ContentSource
 * sur les entités SrdClass, Subclass, Race, Subrace, Monster, Item, Feat, Background.
 *
 * Ordre impératif : enfants (subclass, subrace) avant parents (class, race).
 */
final class Version20260306000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'All SRD entities: ManyToOne source → ManyToMany sources via junction tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE srd_subclass_source (
                subclass_id INT NOT NULL,
                content_source_id INT NOT NULL,
                PRIMARY KEY (subclass_id, content_source_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO srd_subclass_source (subclass_id, content_source_id)
            SELECT DISTINCT c.min_id, s.subclass_source_id
            FROM srd_subclass s
            INNER JOIN (
                SELECT subclass_name, class_id, MIN(subclass_id) AS min_id
                FROM srd_subclass GROUP BY subclass_name, class_id
            ) c ON s.subclass_name = c.subclass_name AND s.class_id = c.class_id
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM srd_subclass WHERE subclass_id NOT IN (
                SELECT min_id FROM (
                    SELECT MIN(subclass_id) AS min_id FROM srd_subclass GROUP BY subclass_name, class_id
                ) AS canonical
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE srd_subclass_source
                ADD CONSTRAINT FK_srd_subclass_source_subclass
                    FOREIGN KEY (subclass_id) REFERENCES srd_subclass (subclass_id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_srd_subclass_source_content
                    FOREIGN KEY (content_source_id) REFERENCES content_source (content_source_id) ON DELETE CASCADE
        SQL);

        $this->addSql('ALTER TABLE srd_subclass DROP FOREIGN KEY FK_9727DD8433E8CC5');
        $this->addSql('ALTER TABLE srd_subclass DROP COLUMN subclass_source_id');

        $this->addSql(<<<'SQL'
            CREATE TABLE srd_subrace_source (
                subrace_id INT NOT NULL,
                content_source_id INT NOT NULL,
                PRIMARY KEY (subrace_id, content_source_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO srd_subrace_source (subrace_id, content_source_id)
            SELECT DISTINCT c.min_id, s.subrace_source_id
            FROM srd_subrace s
            INNER JOIN (
                SELECT subrace_name, race_id, MIN(subrace_id) AS min_id
                FROM srd_subrace GROUP BY subrace_name, race_id
            ) c ON s.subrace_name = c.subrace_name AND s.race_id = c.race_id
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM srd_subrace WHERE subrace_id NOT IN (
                SELECT min_id FROM (
                    SELECT MIN(subrace_id) AS min_id FROM srd_subrace GROUP BY subrace_name, race_id
                ) AS canonical
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE srd_subrace_source
                ADD CONSTRAINT FK_srd_subrace_source_subrace
                    FOREIGN KEY (subrace_id) REFERENCES srd_subrace (subrace_id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_srd_subrace_source_content
                    FOREIGN KEY (content_source_id) REFERENCES content_source (content_source_id) ON DELETE CASCADE
        SQL);

        $this->addSql('ALTER TABLE srd_subrace DROP FOREIGN KEY FK_EDC4F7232C47DB8C');
        $this->addSql('ALTER TABLE srd_subrace DROP COLUMN subrace_source_id');

        $this->addSql(<<<'SQL'
            CREATE TABLE srd_class_source (
                class_id INT NOT NULL,
                content_source_id INT NOT NULL,
                PRIMARY KEY (class_id, content_source_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO srd_class_source (class_id, content_source_id)
            SELECT DISTINCT c.min_id, s.class_source_id
            FROM srd_class s
            INNER JOIN (
                SELECT class_name, MIN(class_id) AS min_id
                FROM srd_class GROUP BY class_name
            ) c ON s.class_name = c.class_name
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM srd_class WHERE class_id NOT IN (
                SELECT min_id FROM (
                    SELECT MIN(class_id) AS min_id FROM srd_class GROUP BY class_name
                ) AS canonical
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE srd_class_source
                ADD CONSTRAINT FK_srd_class_source_class
                    FOREIGN KEY (class_id) REFERENCES srd_class (class_id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_srd_class_source_content
                    FOREIGN KEY (content_source_id) REFERENCES content_source (content_source_id) ON DELETE CASCADE
        SQL);

        $this->addSql('ALTER TABLE srd_class DROP FOREIGN KEY FK_681A4105FEE3835E');
        $this->addSql('ALTER TABLE srd_class DROP COLUMN class_source_id');

        $this->addSql(<<<'SQL'
            CREATE TABLE srd_race_source (
                race_id INT NOT NULL,
                content_source_id INT NOT NULL,
                PRIMARY KEY (race_id, content_source_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO srd_race_source (race_id, content_source_id)
            SELECT DISTINCT c.min_id, s.race_source_id
            FROM srd_race s
            INNER JOIN (
                SELECT race_name, MIN(race_id) AS min_id
                FROM srd_race GROUP BY race_name
            ) c ON s.race_name = c.race_name
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM srd_race WHERE race_id NOT IN (
                SELECT min_id FROM (
                    SELECT MIN(race_id) AS min_id FROM srd_race GROUP BY race_name
                ) AS canonical
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE srd_race_source
                ADD CONSTRAINT FK_srd_race_source_race
                    FOREIGN KEY (race_id) REFERENCES srd_race (race_id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_srd_race_source_content
                    FOREIGN KEY (content_source_id) REFERENCES content_source (content_source_id) ON DELETE CASCADE
        SQL);

        $this->addSql('ALTER TABLE srd_race DROP FOREIGN KEY FK_EE07F9FD4CBA5C7C');
        $this->addSql('ALTER TABLE srd_race DROP COLUMN race_source_id');

        $this->addSql(<<<'SQL'
            CREATE TABLE srd_monster_source (
                monster_id INT NOT NULL,
                content_source_id INT NOT NULL,
                PRIMARY KEY (monster_id, content_source_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO srd_monster_source (monster_id, content_source_id)
            SELECT DISTINCT c.min_id, s.monster_source_id
            FROM srd_monster s
            INNER JOIN (
                SELECT monster_name, MIN(monster_id) AS min_id
                FROM srd_monster GROUP BY monster_name
            ) c ON s.monster_name = c.monster_name
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM srd_monster WHERE monster_id NOT IN (
                SELECT min_id FROM (
                    SELECT MIN(monster_id) AS min_id FROM srd_monster GROUP BY monster_name
                ) AS canonical
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE srd_monster_source
                ADD CONSTRAINT FK_srd_monster_source_monster
                    FOREIGN KEY (monster_id) REFERENCES srd_monster (monster_id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_srd_monster_source_content
                    FOREIGN KEY (content_source_id) REFERENCES content_source (content_source_id) ON DELETE CASCADE
        SQL);

        $this->addSql('ALTER TABLE srd_monster DROP FOREIGN KEY FK_CA40F8F371841726');
        $this->addSql('ALTER TABLE srd_monster DROP COLUMN monster_source_id');

        $this->addSql(<<<'SQL'
            CREATE TABLE srd_item_source (
                item_id INT NOT NULL,
                content_source_id INT NOT NULL,
                PRIMARY KEY (item_id, content_source_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO srd_item_source (item_id, content_source_id)
            SELECT DISTINCT c.min_id, s.item_source_id
            FROM srd_item s
            INNER JOIN (
                SELECT item_name, MIN(item_id) AS min_id
                FROM srd_item GROUP BY item_name
            ) c ON s.item_name = c.item_name
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM srd_item WHERE item_id NOT IN (
                SELECT min_id FROM (
                    SELECT MIN(item_id) AS min_id FROM srd_item GROUP BY item_name
                ) AS canonical
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE srd_item_source
                ADD CONSTRAINT FK_srd_item_source_item
                    FOREIGN KEY (item_id) REFERENCES srd_item (item_id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_srd_item_source_content
                    FOREIGN KEY (content_source_id) REFERENCES content_source (content_source_id) ON DELETE CASCADE
        SQL);

        $this->addSql('ALTER TABLE srd_item DROP FOREIGN KEY FK_2B73674C99E60A91');
        $this->addSql('ALTER TABLE srd_item DROP COLUMN item_source_id');

        $this->addSql(<<<'SQL'
            CREATE TABLE srd_feat_source (
                feat_id INT NOT NULL,
                content_source_id INT NOT NULL,
                PRIMARY KEY (feat_id, content_source_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO srd_feat_source (feat_id, content_source_id)
            SELECT DISTINCT c.min_id, s.feat_source_id
            FROM srd_feat s
            INNER JOIN (
                SELECT feat_name, MIN(feat_id) AS min_id
                FROM srd_feat GROUP BY feat_name
            ) c ON s.feat_name = c.feat_name
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM srd_feat WHERE feat_id NOT IN (
                SELECT min_id FROM (
                    SELECT MIN(feat_id) AS min_id FROM srd_feat GROUP BY feat_name
                ) AS canonical
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE srd_feat_source
                ADD CONSTRAINT FK_srd_feat_source_feat
                    FOREIGN KEY (feat_id) REFERENCES srd_feat (feat_id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_srd_feat_source_content
                    FOREIGN KEY (content_source_id) REFERENCES content_source (content_source_id) ON DELETE CASCADE
        SQL);

        $this->addSql('ALTER TABLE srd_feat DROP FOREIGN KEY FK_6EF3D399705E0009');
        $this->addSql('ALTER TABLE srd_feat DROP COLUMN feat_source_id');

        $this->addSql(<<<'SQL'
            CREATE TABLE srd_background_source (
                background_id INT NOT NULL,
                content_source_id INT NOT NULL,
                PRIMARY KEY (background_id, content_source_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO srd_background_source (background_id, content_source_id)
            SELECT DISTINCT c.min_id, s.background_source_id
            FROM srd_background s
            INNER JOIN (
                SELECT background_name, MIN(background_id) AS min_id
                FROM srd_background GROUP BY background_name
            ) c ON s.background_name = c.background_name
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM srd_background WHERE background_id NOT IN (
                SELECT min_id FROM (
                    SELECT MIN(background_id) AS min_id FROM srd_background GROUP BY background_name
                ) AS canonical
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE srd_background_source
                ADD CONSTRAINT FK_srd_background_source_background
                    FOREIGN KEY (background_id) REFERENCES srd_background (background_id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_srd_background_source_content
                    FOREIGN KEY (content_source_id) REFERENCES content_source (content_source_id) ON DELETE CASCADE
        SQL);

        $this->addSql('ALTER TABLE srd_background DROP FOREIGN KEY FK_2ED842590E7347F');
        $this->addSql('ALTER TABLE srd_background DROP COLUMN background_source_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE srd_background ADD COLUMN background_source_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE srd_background b JOIN (SELECT background_id, MIN(content_source_id) AS sid FROM srd_background_source GROUP BY background_id) s
            ON b.background_id = s.background_id SET b.background_source_id = s.sid
        SQL);
        $this->addSql('ALTER TABLE srd_background MODIFY background_source_id INT NOT NULL');
        $this->addSql('ALTER TABLE srd_background ADD CONSTRAINT FK_2ED842590E7347F FOREIGN KEY (background_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_background_source DROP FOREIGN KEY FK_srd_background_source_background');
        $this->addSql('ALTER TABLE srd_background_source DROP FOREIGN KEY FK_srd_background_source_content');
        $this->addSql('DROP TABLE srd_background_source');

        $this->addSql('ALTER TABLE srd_feat ADD COLUMN feat_source_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE srd_feat f JOIN (SELECT feat_id, MIN(content_source_id) AS sid FROM srd_feat_source GROUP BY feat_id) s
            ON f.feat_id = s.feat_id SET f.feat_source_id = s.sid
        SQL);
        $this->addSql('ALTER TABLE srd_feat MODIFY feat_source_id INT NOT NULL');
        $this->addSql('ALTER TABLE srd_feat ADD CONSTRAINT FK_6EF3D399705E0009 FOREIGN KEY (feat_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_feat_source DROP FOREIGN KEY FK_srd_feat_source_feat');
        $this->addSql('ALTER TABLE srd_feat_source DROP FOREIGN KEY FK_srd_feat_source_content');
        $this->addSql('DROP TABLE srd_feat_source');

        $this->addSql('ALTER TABLE srd_item ADD COLUMN item_source_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE srd_item i JOIN (SELECT item_id, MIN(content_source_id) AS sid FROM srd_item_source GROUP BY item_id) s
            ON i.item_id = s.item_id SET i.item_source_id = s.sid
        SQL);
        $this->addSql('ALTER TABLE srd_item MODIFY item_source_id INT NOT NULL');
        $this->addSql('ALTER TABLE srd_item ADD CONSTRAINT FK_2B73674C99E60A91 FOREIGN KEY (item_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_item_source DROP FOREIGN KEY FK_srd_item_source_item');
        $this->addSql('ALTER TABLE srd_item_source DROP FOREIGN KEY FK_srd_item_source_content');
        $this->addSql('DROP TABLE srd_item_source');

        $this->addSql('ALTER TABLE srd_monster ADD COLUMN monster_source_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE srd_monster m JOIN (SELECT monster_id, MIN(content_source_id) AS sid FROM srd_monster_source GROUP BY monster_id) s
            ON m.monster_id = s.monster_id SET m.monster_source_id = s.sid
        SQL);
        $this->addSql('ALTER TABLE srd_monster MODIFY monster_source_id INT NOT NULL');
        $this->addSql('ALTER TABLE srd_monster ADD CONSTRAINT FK_CA40F8F371841726 FOREIGN KEY (monster_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_monster_source DROP FOREIGN KEY FK_srd_monster_source_monster');
        $this->addSql('ALTER TABLE srd_monster_source DROP FOREIGN KEY FK_srd_monster_source_content');
        $this->addSql('DROP TABLE srd_monster_source');

        $this->addSql('ALTER TABLE srd_race ADD COLUMN race_source_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE srd_race r JOIN (SELECT race_id, MIN(content_source_id) AS sid FROM srd_race_source GROUP BY race_id) s
            ON r.race_id = s.race_id SET r.race_source_id = s.sid
        SQL);
        $this->addSql('ALTER TABLE srd_race MODIFY race_source_id INT NOT NULL');
        $this->addSql('ALTER TABLE srd_race ADD CONSTRAINT FK_EE07F9FD4CBA5C7C FOREIGN KEY (race_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_race_source DROP FOREIGN KEY FK_srd_race_source_race');
        $this->addSql('ALTER TABLE srd_race_source DROP FOREIGN KEY FK_srd_race_source_content');
        $this->addSql('DROP TABLE srd_race_source');

        $this->addSql('ALTER TABLE srd_class ADD COLUMN class_source_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE srd_class c JOIN (SELECT class_id, MIN(content_source_id) AS sid FROM srd_class_source GROUP BY class_id) s
            ON c.class_id = s.class_id SET c.class_source_id = s.sid
        SQL);
        $this->addSql('ALTER TABLE srd_class MODIFY class_source_id INT NOT NULL');
        $this->addSql('ALTER TABLE srd_class ADD CONSTRAINT FK_681A4105FEE3835E FOREIGN KEY (class_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_class_source DROP FOREIGN KEY FK_srd_class_source_class');
        $this->addSql('ALTER TABLE srd_class_source DROP FOREIGN KEY FK_srd_class_source_content');
        $this->addSql('DROP TABLE srd_class_source');

        $this->addSql('ALTER TABLE srd_subrace ADD COLUMN subrace_source_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE srd_subrace sr JOIN (SELECT subrace_id, MIN(content_source_id) AS sid FROM srd_subrace_source GROUP BY subrace_id) s
            ON sr.subrace_id = s.subrace_id SET sr.subrace_source_id = s.sid
        SQL);
        $this->addSql('ALTER TABLE srd_subrace MODIFY subrace_source_id INT NOT NULL');
        $this->addSql('ALTER TABLE srd_subrace ADD CONSTRAINT FK_EDC4F7232C47DB8C FOREIGN KEY (subrace_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_subrace_source DROP FOREIGN KEY FK_srd_subrace_source_subrace');
        $this->addSql('ALTER TABLE srd_subrace_source DROP FOREIGN KEY FK_srd_subrace_source_content');
        $this->addSql('DROP TABLE srd_subrace_source');

        $this->addSql('ALTER TABLE srd_subclass ADD COLUMN subclass_source_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE srd_subclass sc JOIN (SELECT subclass_id, MIN(content_source_id) AS sid FROM srd_subclass_source GROUP BY subclass_id) s
            ON sc.subclass_id = s.subclass_id SET sc.subclass_source_id = s.sid
        SQL);
        $this->addSql('ALTER TABLE srd_subclass MODIFY subclass_source_id INT NOT NULL');
        $this->addSql('ALTER TABLE srd_subclass ADD CONSTRAINT FK_9727DD8433E8CC5 FOREIGN KEY (subclass_source_id) REFERENCES content_source (content_source_id)');
        $this->addSql('ALTER TABLE srd_subclass_source DROP FOREIGN KEY FK_srd_subclass_source_subclass');
        $this->addSql('ALTER TABLE srd_subclass_source DROP FOREIGN KEY FK_srd_subclass_source_content');
        $this->addSql('DROP TABLE srd_subclass_source');
    }
}

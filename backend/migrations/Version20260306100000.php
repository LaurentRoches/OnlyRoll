<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add proficiencies, starting equipment, multiclassing, classTableGroups to srd_class; add subclass_feature table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE srd_class ADD COLUMN class_proficiencies JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE srd_class ADD COLUMN class_starting_equipment JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE srd_class ADD COLUMN class_multiclassing JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE srd_class ADD COLUMN class_table_groups JSON DEFAULT NULL');

        $this->addSql('CREATE TABLE subclass_feature (
            subclass_feature_id INT AUTO_INCREMENT NOT NULL,
            subclass_id INT NOT NULL,
            feature_name VARCHAR(200) NOT NULL,
            feature_level SMALLINT NOT NULL,
            feature_description LONGTEXT DEFAULT NULL,
            PRIMARY KEY (subclass_feature_id),
            CONSTRAINT FK_subclass_feature_subclass FOREIGN KEY (subclass_id)
                REFERENCES srd_subclass (subclass_id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE subclass_feature');
        $this->addSql('ALTER TABLE srd_class DROP COLUMN class_proficiencies');
        $this->addSql('ALTER TABLE srd_class DROP COLUMN class_starting_equipment');
        $this->addSql('ALTER TABLE srd_class DROP COLUMN class_multiclassing');
        $this->addSql('ALTER TABLE srd_class DROP COLUMN class_table_groups');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée les tables de traduction SRD pour le support multilingue (fr/en).
 */
final class Version20260315120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create srd_translation and srd_child_translation tables for multilingual SRD data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE srd_translation (
                srd_translation_id INT AUTO_INCREMENT NOT NULL,
                srd_table VARCHAR(30) NOT NULL,
                srd_id INT NOT NULL,
                locale VARCHAR(5) NOT NULL,
                translated_fields JSON NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_srd_translation_unique (srd_table, srd_id, locale),
                INDEX IDX_srd_translation_table_locale (srd_table, locale),
                PRIMARY KEY(srd_translation_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE srd_child_translation (
                srd_child_translation_id INT AUTO_INCREMENT NOT NULL,
                child_table VARCHAR(50) NOT NULL,
                child_id INT NOT NULL,
                locale VARCHAR(5) NOT NULL,
                translated_fields JSON NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_srd_child_translation_unique (child_table, child_id, locale),
                INDEX IDX_srd_child_translation_table_locale (child_table, locale),
                PRIMARY KEY(srd_child_translation_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE srd_child_translation');
        $this->addSql('DROP TABLE srd_translation');
    }
}

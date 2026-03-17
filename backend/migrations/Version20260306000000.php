<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refactoring : passage de Spell→ContentSource de ManyToOne vers ManyToMany.
 * Crée la table de jonction srd_spell_source, migre les données,
 * supprime les doublons et retire la colonne spell_source_id de srd_spell.
 */
final class Version20260306000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Spell: ManyToOne source → ManyToMany sources via srd_spell_source junction table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE srd_spell_source (
                spell_id INT NOT NULL,
                content_source_id INT NOT NULL,
                PRIMARY KEY (spell_id, content_source_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO srd_spell_source (spell_id, content_source_id)
            SELECT MIN(s.spell_id), s.spell_source_id
            FROM srd_spell s
            GROUP BY s.spell_name, s.spell_source_id
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM srd_spell
            WHERE spell_id NOT IN (
                SELECT min_id FROM (
                    SELECT MIN(spell_id) AS min_id FROM srd_spell GROUP BY spell_name
                ) AS canonical
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE srd_spell_source
                ADD CONSTRAINT FK_srd_spell_source_spell
                    FOREIGN KEY (spell_id) REFERENCES srd_spell (spell_id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_srd_spell_source_content
                    FOREIGN KEY (content_source_id) REFERENCES content_source (content_source_id) ON DELETE CASCADE
        SQL);

        $this->addSql('ALTER TABLE srd_spell DROP FOREIGN KEY FK_556E951775FD9946');
        $this->addSql('ALTER TABLE srd_spell DROP COLUMN spell_source_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE srd_spell ADD COLUMN spell_source_id INT DEFAULT NULL');

        $this->addSql(<<<'SQL'
            UPDATE srd_spell s
            JOIN (
                SELECT spell_id, MIN(content_source_id) AS content_source_id
                FROM srd_spell_source
                GROUP BY spell_id
            ) ss ON s.spell_id = ss.spell_id
            SET s.spell_source_id = ss.content_source_id
        SQL);

        $this->addSql('ALTER TABLE srd_spell MODIFY COLUMN spell_source_id INT NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE srd_spell
                ADD CONSTRAINT FK_556E951775FD9946
                    FOREIGN KEY (spell_source_id) REFERENCES content_source (content_source_id)
        SQL);

        $this->addSql('ALTER TABLE srd_spell_source DROP FOREIGN KEY FK_srd_spell_source_spell');
        $this->addSql('ALTER TABLE srd_spell_source DROP FOREIGN KEY FK_srd_spell_source_content');
        $this->addSql('DROP TABLE srd_spell_source');
    }
}

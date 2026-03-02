<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228230328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix GameToken size column type from decimal to float, update enum column lengths';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log CHANGE log_ip_address log_ip_address VARCHAR(64) DEFAULT NULL');
        // Rename index only if it exists with old name
        $oldIndexCount = (int) $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'audit_log' AND index_name = 'fk_f6e1c0f5d7c01058'",
        )->fetchOne();
        if ($oldIndexCount > 0) {
            $this->addSql('ALTER TABLE audit_log RENAME INDEX fk_f6e1c0f5d7c01058 TO IDX_F6E1C0F58A5A5BA8');
        }
        $this->addSql('ALTER TABLE game_map CHANGE map_grid_type map_grid_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE game_message CHANGE message_type message_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE game_token CHANGE token_type token_type VARCHAR(255) NOT NULL, CHANGE token_size token_size DOUBLE PRECISION NOT NULL, CHANGE token_layer token_layer VARCHAR(255) NOT NULL');
        // Drop indexes only if they exist
        $activeIndexCount = (int) $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'user' AND index_name = 'idx_user_active'",
        )->fetchOne();
        if ($activeIndexCount > 0) {
            $this->addSql('DROP INDEX idx_user_active ON user');
        }
        $deletedIndexCount = (int) $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'user' AND index_name = 'idx_user_deleted'",
        )->fetchOne();
        if ($deletedIndexCount > 0) {
            $this->addSql('DROP INDEX idx_user_deleted ON user');
        }
        $this->addSql('ALTER TABLE user CHANGE user_is_active user_is_active TINYINT(1) NOT NULL, CHANGE user_failed_login_attempts user_failed_login_attempts INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_map CHANGE map_grid_type map_grid_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE game_message CHANGE message_type message_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE game_token CHANGE token_type token_type VARCHAR(20) NOT NULL, CHANGE token_size token_size NUMERIC(3, 1) NOT NULL, CHANGE token_layer token_layer VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE audit_log CHANGE log_ip_address log_ip_address VARCHAR(64) DEFAULT NULL COMMENT \'Hashed IP for GDPR compliance\'');
        $newIndexCount = (int) $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'audit_log' AND index_name = 'IDX_F6E1C0F58A5A5BA8'",
        )->fetchOne();
        if ($newIndexCount > 0) {
            $this->addSql('ALTER TABLE audit_log RENAME INDEX IDX_F6E1C0F58A5A5BA8 TO FK_F6E1C0F5D7C01058');
        }
        $this->addSql('ALTER TABLE user CHANGE user_is_active user_is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE user_failed_login_attempts user_failed_login_attempts INT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_user_active ON user (user_is_active)');
        $this->addSql('CREATE INDEX idx_user_deleted ON user (user_deleted_at)');
    }
}

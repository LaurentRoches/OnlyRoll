<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260131160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sprint 1: Add security fields to user table and create audit_log table for OWASP compliance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD user_is_active TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE user ADD user_deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user ADD user_failed_login_attempts INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user ADD user_locked_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE INDEX idx_user_active ON user (user_is_active)');
        $this->addSql('CREATE INDEX idx_user_deleted ON user (user_deleted_at)');

        $this->addSql('CREATE TABLE audit_log (
            log_id INT AUTO_INCREMENT NOT NULL,
            log_user_id INT DEFAULT NULL,
            log_target_user_id INT DEFAULT NULL,
            log_action VARCHAR(50) NOT NULL,
            log_entity_type VARCHAR(50) DEFAULT NULL,
            log_entity_id INT DEFAULT NULL,
            log_details JSON DEFAULT NULL,
            log_ip_address VARCHAR(64) DEFAULT NULL COMMENT \'Hashed IP for GDPR compliance\',
            log_user_agent VARCHAR(500) DEFAULT NULL,
            log_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_log_user (log_user_id),
            INDEX idx_log_action (log_action),
            INDEX idx_log_created (log_created_at),
            PRIMARY KEY(log_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5A76ED395 FOREIGN KEY (log_user_id) REFERENCES user (user_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5D7C01058 FOREIGN KEY (log_target_user_id) REFERENCES user (user_id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5A76ED395');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5D7C01058');

        $this->addSql('DROP TABLE audit_log');

        $this->addSql('DROP INDEX idx_user_active ON user');
        $this->addSql('DROP INDEX idx_user_deleted ON user');

        $this->addSql('ALTER TABLE user DROP user_is_active');
        $this->addSql('ALTER TABLE user DROP user_deleted_at');
        $this->addSql('ALTER TABLE user DROP user_failed_login_attempts');
        $this->addSql('ALTER TABLE user DROP user_locked_until');
    }
}

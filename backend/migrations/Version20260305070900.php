<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * No-op migration.
 */
final class Version20260305070900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'No-op: superseded by Version20260305070000';
    }

    public function up(Schema $schema): void
    {
        // All FK constraints are now created by Version20260305070000.
    }

    public function down(Schema $schema): void
    {
        // No-op.
    }
}

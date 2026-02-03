<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260131170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sprint 2: Grant ROLE_ADMIN to rocheslaurent@gmail.com';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE user SET user_roles = ? WHERE user_email = ?', [
            '["ROLE_USER", "ROLE_ADMIN"]',
            'rocheslaurent@gmail.com',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE user SET user_roles = ? WHERE user_email = ?', [
            '["ROLE_USER"]',
            'rocheslaurent@gmail.com',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add description column to srd_subclass';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE srd_subclass ADD COLUMN subclass_description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE srd_subclass DROP COLUMN subclass_description');
    }
}

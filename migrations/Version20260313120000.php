<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_rental boolean to transport table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transport ADD is_rental TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transport DROP COLUMN is_rental');
    }
}

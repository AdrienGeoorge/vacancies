<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplace is_story par expires_at sur trip_photo (null = photo permanente, date = story éphémère)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trip_photo DROP COLUMN is_story');
        $this->addSql('ALTER TABLE trip_photo ADD expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trip_photo DROP COLUMN expires_at');
        $this->addSql('ALTER TABLE trip_photo ADD is_story TINYINT(1) NOT NULL DEFAULT 0');
    }
}

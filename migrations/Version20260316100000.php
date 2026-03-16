<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260316100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename story_token to share_token, add is_public and public_slug, drop visibility';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trip CHANGE story_token share_token VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE trip ADD is_public TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE trip ADD public_slug VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE trip DROP visibility');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_trip_public_slug ON trip (public_slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_trip_public_slug ON trip');
        $this->addSql('ALTER TABLE trip CHANGE share_token story_token VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE trip DROP is_public');
        $this->addSql('ALTER TABLE trip DROP public_slug');
        $this->addSql('ALTER TABLE trip ADD visibility VARCHAR(9) DEFAULT NULL');
    }
}

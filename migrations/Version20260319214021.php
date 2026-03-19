<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319214021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_trip_public_slug ON trip');
        $this->addSql('ALTER TABLE trip DROP is_public, DROP public_slug');
        $this->addSql('ALTER TABLE trip RENAME INDEX uniq_7656f53b83770198 TO UNIQ_7656F53BD6594DD6');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trip ADD is_public TINYINT(1) DEFAULT 0 NOT NULL, ADD public_slug VARCHAR(120) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_trip_public_slug ON trip (public_slug)');
        $this->addSql('ALTER TABLE trip RENAME INDEX uniq_7656f53bd6594dd6 TO UNIQ_7656F53B83770198');
    }
}

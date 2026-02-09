<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209224729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trip_destination RENAME INDEX idx_trip_destination_trip TO IDX_CAE79920A5BC2E0E');
        $this->addSql('ALTER TABLE trip_destination RENAME INDEX idx_trip_destination_country TO IDX_CAE79920F92F3E70');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trip_destination RENAME INDEX idx_cae79920a5bc2e0e TO IDX_trip_destination_trip');
        $this->addSql('ALTER TABLE trip_destination RENAME INDEX idx_cae79920f92f3e70 TO IDX_trip_destination_country');
    }
}

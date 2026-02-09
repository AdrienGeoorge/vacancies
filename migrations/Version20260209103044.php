<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209103044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE trip_destination (
        id INT AUTO_INCREMENT NOT NULL,
        trip_id INT NOT NULL,
        country_id INT NOT NULL,
        display_order INT NOT NULL,
        departure_date DATE DEFAULT NULL,
        return_date DATE DEFAULT NULL,
        INDEX IDX_trip_destination_trip (trip_id),
        INDEX IDX_trip_destination_country (country_id),
        PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE trip_destination 
        ADD CONSTRAINT FK_trip_destination_trip 
        FOREIGN KEY (trip_id) REFERENCES trip (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE trip_destination 
        ADD CONSTRAINT FK_trip_destination_country 
        FOREIGN KEY (country_id) REFERENCES country (id)');

        // 2. Migrer les données existantes (country → destinations)
        $this->addSql('
        INSERT INTO trip_destination (trip_id, country_id, display_order)
        SELECT id, country_id, 0
        FROM trip
        WHERE country_id IS NOT NULL
    ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trip_destination DROP FOREIGN KEY FK_trip_destination_trip');
        $this->addSql('ALTER TABLE trip_destination DROP FOREIGN KEY FK_trip_destination_country');
        $this->addSql('DROP TABLE trip_destination');
    }
}

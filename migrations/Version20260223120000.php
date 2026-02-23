<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table cities et migre les coordonnées GPS depuis climate_data';
    }

    public function up(Schema $schema): void
    {
        // 1. Créer la table cities
        $this->addSql('CREATE TABLE cities (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            country VARCHAR(100) DEFAULT NULL,
            latitude DECIMAL(10,7) NOT NULL,
            longitude DECIMAL(10,7) NOT NULL,
            UNIQUE INDEX unique_city_country (name, country),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. Migrer les coordonnées existantes depuis climate_data
        $this->addSql('INSERT INTO cities (name, country, latitude, longitude)
            SELECT DISTINCT city, country, latitude, longitude
            FROM climate_data
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL');

        // 3. Supprimer les colonnes lat/lon de climate_data
        $this->addSql('ALTER TABLE climate_data DROP COLUMN latitude, DROP COLUMN longitude');
    }

    public function down(Schema $schema): void
    {
        // 1. Recréer les colonnes lat/lon dans climate_data
        $this->addSql('ALTER TABLE climate_data
            ADD COLUMN latitude DECIMAL(10,7) DEFAULT NULL,
            ADD COLUMN longitude DECIMAL(10,7) DEFAULT NULL');

        // 2. Restaurer les coordonnées depuis cities
        $this->addSql('UPDATE climate_data cd
            INNER JOIN cities ci ON ci.name = cd.city AND ci.country = cd.country
            SET cd.latitude = ci.latitude, cd.longitude = ci.longitude');

        // 3. Supprimer la table cities
        $this->addSql('DROP TABLE cities');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate wishlist_item.country (string) to country_id FK on country table';
    }

    public function up(Schema $schema): void
    {
        // 1. Ajouter la colonne country_id nullable
        $this->addSql('ALTER TABLE wishlist_item ADD COLUMN country_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_WISHLIST_COUNTRY FOREIGN KEY (country_id) REFERENCES country(id)');

        // 2. Migrer les données existantes : faire correspondre le nom avec country.name
        $this->addSql('
            UPDATE wishlist_item wi
            JOIN country c ON c.name = wi.country
            SET wi.country_id = c.id
        ');

        // 3. Supprimer l'ancienne colonne country
        $this->addSql('ALTER TABLE wishlist_item DROP COLUMN country');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wishlist_item ADD COLUMN country VARCHAR(100) DEFAULT NULL');

        $this->addSql('
            UPDATE wishlist_item wi
            JOIN country c ON c.id = wi.country_id
            SET wi.country = c.name
        ');

        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_WISHLIST_COUNTRY');
        $this->addSql('ALTER TABLE wishlist_item DROP COLUMN country_id');
    }
}

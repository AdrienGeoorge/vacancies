<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209104230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53BF92F3E70');
        $this->addSql('ALTER TABLE trip DROP COLUMN country_id');
    }

    public function down(Schema $schema): void
    {
        // Recréer la colonne si besoin de rollback
        $this->addSql('ALTER TABLE trip ADD country_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_trip_country FOREIGN KEY (country_id) REFERENCES country (id)');
    }
}

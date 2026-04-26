<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426160638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE event_type ADD COLUMN position INT NOT NULL DEFAULT 0;");

        $this->addSql("UPDATE event_type SET name = 'Musée et exposition', position = 1 WHERE name = 'Musee et expo'");
        $this->addSql("INSERT INTO event_type (name, color, position) VALUES ('Site historique et monument', '#8d6e63', 2)");
        $this->addSql("INSERT INTO event_type (name, color, position) VALUES ('Visite guidée', '#f4a261', 3)");
        $this->addSql("UPDATE event_type SET name = 'Spectacle', position = 4 WHERE name = 'Spectacles'");

        $this->addSql("UPDATE event_type SET position = 5 WHERE name = 'Balade'");
        $this->addSql("INSERT INTO event_type (name, color, position) VALUES ('Randonnée', '#2a9d8f', 6)");
        $this->addSql("INSERT INTO event_type (name, color, position) VALUES ('Plage', '#00b4d8', 7)");
        $this->addSql("INSERT INTO event_type (name, color, position) VALUES ('Parc naturel et jardin', '#55a630', 8)");
        $this->addSql("INSERT INTO event_type (name, color, position) VALUES ('Activité sportive', '#f77f00', 9)");

        $this->addSql("UPDATE event_type SET position = 10 WHERE name = 'Restauration'");
        $this->addSql("INSERT INTO event_type (name, color, position) VALUES ('Bar et vie nocturne', '#3a0ca3', 11)");

        $this->addSql("UPDATE event_type SET position = 12 WHERE name = 'Parc d\'attraction'");
        $this->addSql("INSERT INTO event_type (name, color, position) VALUES ('Festival et concert', '#912152', 13)");
        $this->addSql("INSERT INTO event_type (name, color, position) VALUES ('Bien-être et détente', '#80BACF', 14)");

        $this->addSql("UPDATE event_type SET position = 15 WHERE name = 'Transport'");
        $this->addSql("UPDATE event_type SET position = 16 WHERE name = 'Shopping'");
        $this->addSql("UPDATE event_type SET position = 17 WHERE name = 'Autre'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

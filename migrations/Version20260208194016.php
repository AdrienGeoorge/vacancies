<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208194016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE climate_data (id INT AUTO_INCREMENT NOT NULL, city VARCHAR(100) NOT NULL, month INT NOT NULL, temp_min_avg NUMERIC(4, 1) DEFAULT NULL, temp_max_avg NUMERIC(4, 1) DEFAULT NULL, precipitation_mm NUMERIC(5, 1) DEFAULT NULL, rainy_days INT DEFAULT NULL, sunshine_hours NUMERIC(3, 1) DEFAULT NULL, humidity_avg INT DEFAULT NULL, source VARCHAR(100) DEFAULT NULL, last_updated DATETIME NOT NULL, UNIQUE INDEX unique_city_month (city, month), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE climate_data');
    }
}

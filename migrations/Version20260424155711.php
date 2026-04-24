<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424155711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accommodation ADD purchase_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE activity ADD purchase_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE transport ADD purchase_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE various_expensive ADD purchase_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accommodation DROP purchase_date');
        $this->addSql('ALTER TABLE activity DROP purchase_date');
        $this->addSql('ALTER TABLE country CHANGE timezone timezone VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE exchange_rate CHANGE rates rates LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE various_expensive DROP purchase_date');
        $this->addSql('ALTER TABLE wishlist_item RENAME INDEX idx_6424f4e8f92f3e70 TO FK_WISHLIST_COUNTRY');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424132311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B3397707A');
        $this->addSql('DROP INDEX FK_7656F53B3397707A ON trip');
        $this->addSql('ALTER TABLE trip CHANGE currency_code currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B6956883F FOREIGN KEY (currency) REFERENCES currency (code)');
        $this->addSql('CREATE INDEX IDX_7656F53B6956883F ON trip (currency)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE country CHANGE timezone timezone VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE exchange_rate CHANGE rates rates LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B6956883F');
        $this->addSql('DROP INDEX IDX_7656F53B6956883F ON trip');
        $this->addSql('ALTER TABLE trip CHANGE currency currency_code VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B3397707A FOREIGN KEY (currency_code) REFERENCES currency (code)');
        $this->addSql('CREATE INDEX FK_7656F53B3397707A ON trip (currency_code)');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE wishlist_item RENAME INDEX idx_6424f4e8f92f3e70 TO FK_WISHLIST_COUNTRY');
    }
}

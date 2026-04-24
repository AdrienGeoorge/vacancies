<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424110000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trip ADD currency_code VARCHAR(3) DEFAULT NULL');
        $this->addSql('UPDATE trip SET currency_code = \'EUR\'');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B3397707A FOREIGN KEY (currency_code) REFERENCES currency (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B3397707A');
        $this->addSql('ALTER TABLE trip DROP COLUMN currency_code');
    }
}

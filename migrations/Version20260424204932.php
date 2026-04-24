<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424204932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add preferred_currency_code to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD preferred_currency_code VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_user_preferred_currency FOREIGN KEY (preferred_currency_code) REFERENCES currency (code) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_user_preferred_currency');
        $this->addSql('ALTER TABLE `user` DROP COLUMN preferred_currency_code');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment handles (PayPal, Revolut, Lydia) to User entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD paypal_handle VARCHAR(255) DEFAULT NULL, ADD revolut_handle VARCHAR(255) DEFAULT NULL, ADD lydia_handle VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP paypal_handle, DROP revolut_handle, DROP lydia_handle');
    }
}

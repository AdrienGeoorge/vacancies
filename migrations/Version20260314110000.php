<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove lydia_handle from User entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP lydia_handle');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD lydia_handle VARCHAR(255) DEFAULT NULL');
    }
}

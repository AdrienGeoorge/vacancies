<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426181155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE event_type SET color = '#08c180' WHERE name = 'Randonnée'");
        $this->addSql("UPDATE event_type SET color = '#e5c221' WHERE name = 'Plage'");
        $this->addSql("UPDATE event_type SET color = '#f76800' WHERE name = 'Activité sportive'");
        $this->addSql("UPDATE event_type SET color = '#dd2b2b' WHERE name = 'Autre'");
    }

    public function down(Schema $schema): void
    {
    }
}

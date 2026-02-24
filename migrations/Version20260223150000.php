<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Génère un story_token pour les voyages existants et passe la colonne NOT NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE trip SET story_token = LOWER(HEX(RANDOM_BYTES(32))) WHERE story_token IS NULL');
        $this->addSql('ALTER TABLE trip MODIFY story_token VARCHAR(64) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trip MODIFY story_token VARCHAR(64) DEFAULT NULL');
    }
}

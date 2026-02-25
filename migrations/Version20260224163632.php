<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224163632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trip RENAME INDEX uniq_story_token TO UNIQ_7656F53B83770198');
        $this->addSql('ALTER TABLE trip_photo ADD title VARCHAR(255) DEFAULT NULL, CHANGE caption caption LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE trip_photo RENAME INDEX idx_trip_photo_trip TO IDX_CEFC045BA5BC2E0E');
        $this->addSql('ALTER TABLE trip_photo RENAME INDEX idx_trip_photo_user TO IDX_CEFC045BA2B28FE8');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trip RENAME INDEX uniq_7656f53b83770198 TO UNIQ_story_token');
        $this->addSql('ALTER TABLE trip_photo DROP title, CHANGE caption caption VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE trip_photo RENAME INDEX idx_cefc045ba2b28fe8 TO IDX_trip_photo_user');
        $this->addSql('ALTER TABLE trip_photo RENAME INDEX idx_cefc045ba5bc2e0e TO IDX_trip_photo_trip');
    }
}

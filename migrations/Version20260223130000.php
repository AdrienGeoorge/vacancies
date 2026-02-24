<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table trip_photo et ajoute story_token sur trip';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE trip_photo (
            id INT AUTO_INCREMENT NOT NULL,
            trip_id INT NOT NULL,
            uploaded_by_id INT DEFAULT NULL,
            file VARCHAR(500) NOT NULL,
            caption VARCHAR(255) DEFAULT NULL,
            is_story TINYINT(1) NOT NULL DEFAULT 0,
            uploaded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_trip_photo_trip (trip_id),
            INDEX IDX_trip_photo_user (uploaded_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE trip_photo
            ADD CONSTRAINT FK_trip_photo_trip FOREIGN KEY (trip_id) REFERENCES trip(id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_trip_photo_user FOREIGN KEY (uploaded_by_id) REFERENCES `user`(id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE trip ADD story_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_story_token ON trip (story_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trip_photo DROP FOREIGN KEY FK_trip_photo_trip');
        $this->addSql('ALTER TABLE trip_photo DROP FOREIGN KEY FK_trip_photo_user');
        $this->addSql('DROP TABLE trip_photo');
        $this->addSql('DROP INDEX UNIQ_story_token ON trip');
        $this->addSql('ALTER TABLE trip DROP COLUMN story_token');
    }
}

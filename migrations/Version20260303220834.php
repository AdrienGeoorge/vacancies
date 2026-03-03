<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260303220834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE trip_reimbursement (id INT AUTO_INCREMENT NOT NULL, trip_id INT NOT NULL, from_traveler_id INT NOT NULL, to_traveler_id INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, description VARCHAR(255) DEFAULT NULL, date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F983A41CA5BC2E0E (trip_id), INDEX IDX_F983A41CC7F4D9C6 (from_traveler_id), INDEX IDX_F983A41C7728D6C0 (to_traveler_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE trip_reimbursement ADD CONSTRAINT FK_F983A41CA5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id)');
        $this->addSql('ALTER TABLE trip_reimbursement ADD CONSTRAINT FK_F983A41CC7F4D9C6 FOREIGN KEY (from_traveler_id) REFERENCES trip_traveler (id)');
        $this->addSql('ALTER TABLE trip_reimbursement ADD CONSTRAINT FK_F983A41C7728D6C0 FOREIGN KEY (to_traveler_id) REFERENCES trip_traveler (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trip_reimbursement DROP FOREIGN KEY FK_F983A41CA5BC2E0E');
        $this->addSql('ALTER TABLE trip_reimbursement DROP FOREIGN KEY FK_F983A41CC7F4D9C6');
        $this->addSql('ALTER TABLE trip_reimbursement DROP FOREIGN KEY FK_F983A41C7728D6C0');
        $this->addSql('DROP TABLE trip_reimbursement');
    }
}

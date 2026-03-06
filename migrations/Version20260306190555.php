<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260306190555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE checklist_item (id INT AUTO_INCREMENT NOT NULL, trip_id INT NOT NULL, owner_id INT NOT NULL, checked_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, category VARCHAR(100) DEFAULT NULL, is_checked TINYINT(1) NOT NULL, is_shared TINYINT(1) NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_99EB20F9A5BC2E0E (trip_id), INDEX IDX_99EB20F97E3C61F9 (owner_id), INDEX IDX_99EB20F92199DB86 (checked_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE checklist_template (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_CA6463417E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE checklist_template_item (id INT AUTO_INCREMENT NOT NULL, template_id INT NOT NULL, name VARCHAR(255) NOT NULL, category VARCHAR(100) DEFAULT NULL, is_shared TINYINT(1) NOT NULL, position INT NOT NULL, INDEX IDX_70F520295DA0FB8 (template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE checklist_item ADD CONSTRAINT FK_99EB20F9A5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id)');
        $this->addSql('ALTER TABLE checklist_item ADD CONSTRAINT FK_99EB20F97E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE checklist_item ADD CONSTRAINT FK_99EB20F92199DB86 FOREIGN KEY (checked_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE checklist_template ADD CONSTRAINT FK_CA6463417E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE checklist_template_item ADD CONSTRAINT FK_70F520295DA0FB8 FOREIGN KEY (template_id) REFERENCES checklist_template (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE checklist_item DROP FOREIGN KEY FK_99EB20F9A5BC2E0E');
        $this->addSql('ALTER TABLE checklist_item DROP FOREIGN KEY FK_99EB20F97E3C61F9');
        $this->addSql('ALTER TABLE checklist_item DROP FOREIGN KEY FK_99EB20F92199DB86');
        $this->addSql('ALTER TABLE checklist_template DROP FOREIGN KEY FK_CA6463417E3C61F9');
        $this->addSql('ALTER TABLE checklist_template_item DROP FOREIGN KEY FK_70F520295DA0FB8');
        $this->addSql('DROP TABLE checklist_item');
        $this->addSql('DROP TABLE checklist_template');
        $this->addSql('DROP TABLE checklist_template_item');
    }
}

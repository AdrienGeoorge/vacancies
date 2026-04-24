<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424205829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_user_preferred_currency');
        $this->addSql('DROP INDEX FK_user_preferred_currency ON user');
        $this->addSql('ALTER TABLE user CHANGE preferred_currency_code preferred_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6497F90B450 FOREIGN KEY (preferred_currency) REFERENCES currency (code)');
        $this->addSql('CREATE INDEX IDX_8D93D6497F90B450 ON user (preferred_currency)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6497F90B450');
        $this->addSql('DROP INDEX IDX_8D93D6497F90B450 ON `user`');
        $this->addSql('ALTER TABLE `user` CHANGE preferred_currency preferred_currency_code VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_user_preferred_currency FOREIGN KEY (preferred_currency_code) REFERENCES currency (code) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX FK_user_preferred_currency ON `user` (preferred_currency_code)');
    }
}

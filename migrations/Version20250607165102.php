<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250607165102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD username VARCHAR(25) NOT NULL, ADD is_private_profile TINYINT(1) NOT NULL DEFAULT FALSE;');
        $this->addSql("UPDATE user
                            SET username = CASE
                                WHEN firstname IS NOT NULL AND firstname != ''
                                     AND lastname IS NOT NULL AND lastname != ''
                                THEN LOWER(CONCAT(firstname, lastname))
                                ELSE LEFT(UUID(), 10)
                            END
                            WHERE true;");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP username, DROP is_private_profile');
    }
}

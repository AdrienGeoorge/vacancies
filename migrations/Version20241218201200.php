<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241218201200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `transport_type` (`id`, `name`) VALUES (NULL, 'Transports en commun')");
        $this->addSql("INSERT INTO `transport_type` (`id`, `name`) VALUES (NULL, 'Train')");
        $this->addSql("INSERT INTO `transport_type` (`id`, `name`) VALUES (NULL, 'Avion')");
        $this->addSql("INSERT INTO `transport_type` (`id`, `name`) VALUES (NULL, 'Bus')");
        $this->addSql("INSERT INTO `transport_type` (`id`, `name`) VALUES (NULL, 'Voiture')");
        $this->addSql("INSERT INTO `transport_type` (`id`, `name`) VALUES (NULL, 'Autre')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}

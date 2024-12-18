<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241219163445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `event_type` (`id`, `name`, `color`) VALUES (NULL, 'Musée et expo', '#c0aa68')");
        $this->addSql("INSERT INTO `event_type` (`id`, `name`, `color`) VALUES (NULL, 'Parc d''attraction', '#b0c596')");
        $this->addSql("INSERT INTO `event_type` (`id`, `name`, `color`) VALUES (NULL, 'Spectacles', '#60988b')");
        $this->addSql("INSERT INTO `event_type` (`id`, `name`, `color`) VALUES (NULL, 'Balade', '#41accc')");
        $this->addSql("INSERT INTO `event_type` (`id`, `name`, `color`) VALUES (NULL, 'Restauration', '#836bde')");
        $this->addSql("INSERT INTO `event_type` (`id`, `name`, `color`) VALUES (NULL, 'Transport', '#d36bde')");
        $this->addSql("INSERT INTO `event_type` (`id`, `name`, `color`) VALUES (NULL, 'Shopping', '#de6b6b')");
        $this->addSql("INSERT INTO `event_type` (`id`, `name`, `color`) VALUES (NULL, 'Autre', '#de976b')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}

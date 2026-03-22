<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing countries, special territories and French DOM-TOM';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO currency (code, name, symbol) VALUES ('KPW', 'Won nord-coréen', '₩')");

        // Pays manquants (standard)
        $this->addSql("INSERT INTO country (code, name, continent, currency, plug_types, timezone, capital) VALUES
              ('HU', 'Hongrie', 'Europe', 'HUF', 'C,F', 'UTC+1', 'Budapest'),
              ('CZ', 'République tchèque', 'Europe', 'CZK', 'C,E', 'UTC+1', 'Prague'),
              ('BY', 'Biélorussie', 'Europe', 'BYN', 'C,F', 'UTC+2', 'Minsk'),
              ('KG', 'Kirghizistan', 'Asie', 'KGS', 'C,F', 'UTC+6', 'Bichkek'),
              ('KP', 'Corée du Nord', 'Asie', 'KPW', 'A,C', 'UTC+9', 'Pyongyang'),
              ('AG', 'Antigua-et-Barbuda', 'Amérique du Nord', 'XCD', 'A,B', 'UTC-4', 'Saint John''s')
          ");

        // Territoires spéciaux
        $this->addSql("INSERT INTO country (code, name, continent, currency, plug_types, timezone, capital) VALUES
              ('HK', 'Hong Kong', 'Asie', 'HKD', 'G', 'UTC+8', 'Hong Kong'),
              ('MO', 'Macao', 'Asie', 'MOP', 'G', 'UTC+8', 'Macao'),
              ('XK', 'Kosovo', 'Europe', 'EUR', 'C,F', 'UTC+1', 'Pristina'),
              ('PS', 'Palestine', 'Asie', 'ILS', 'C,H', 'UTC+2', 'Ramallah')
          ");

        // DOM-TOM français
        $this->addSql("INSERT INTO country (code, name, continent, currency, plug_types, timezone, capital) VALUES
              ('GP', 'Guadeloupe', 'Amérique du Nord', 'EUR', 'C,E', 'UTC-4', 'Basse-Terre'),
              ('MQ', 'Martinique', 'Amérique du Nord', 'EUR', 'C,E', 'UTC-4', 'Fort-de-France'),
              ('GF', 'Guyane française', 'Amérique du Sud', 'EUR', 'C,E', 'UTC-3', 'Cayenne'),
              ('RE', 'La Réunion', 'Afrique', 'EUR', 'C,E', 'UTC+4', 'Saint-Denis'),
              ('YT', 'Mayotte', 'Afrique', 'EUR', 'C,E', 'UTC+3', 'Mamoudzou'),
              ('NC', 'Nouvelle-Calédonie', 'Océanie', 'XPF', 'C,E', 'UTC+11', 'Nouméa'),
              ('PF', 'Polynésie française', 'Océanie', 'XPF', 'C,E', 'UTC-10 à UTC-9', 'Papeete'),
              ('PM', 'Saint-Pierre-et-Miquelon', 'Amérique du Nord', 'EUR', 'C,E', 'UTC-3', 'Saint-Pierre'),
              ('BL', 'Saint-Barthélemy', 'Amérique du Nord', 'EUR', 'A,B,C,E', 'UTC-4', 'Gustavia'),
              ('MF', 'Saint-Martin', 'Amérique du Nord', 'EUR', 'C,E', 'UTC-4', 'Marigot'),
              ('WF', 'Wallis-et-Futuna', 'Océanie', 'XPF', 'C,E', 'UTC+12', 'Mata-Utu')
          ");

        $this->addSql("INSERT INTO country (code, name, continent, currency, plug_types, timezone, capital) VALUES
              ('AW', 'Aruba', 'Amérique du Nord', 'AWG', 'A,B', 'UTC-4', 'Oranjestad'),
              ('CW', 'Curaçao', 'Amérique du Nord', 'ANG', 'A,B', 'UTC-4', 'Willemstad'),
              ('KY', 'Îles Caïmans', 'Amérique du Nord', 'KYD', 'G', 'UTC-5', 'George Town'),
              ('BM', 'Bermudes', 'Amérique du Nord', 'BMD', 'G', 'UTC-3', 'Hamilton'),
              ('VG', 'Îles Vierges britanniques', 'Amérique du Nord', 'USD', 'A,B', 'UTC-4', 'Road Town'),
              ('TC', 'Turks-et-Caïcos', 'Amérique du Nord', 'USD', 'A,B', 'UTC-5', 'Cockburn Town'),
              ('PR', 'Porto Rico', 'Amérique du Nord', 'USD', 'A,B', 'UTC-4', 'San Juan'),
              ('GI', 'Gibraltar', 'Europe', 'GIP', 'G', 'UTC+1', 'Gibraltar'),
              ('FO', 'Îles Féroé', 'Europe', 'DKK', 'C,E,F,K', 'UTC+0', 'Tórshavn'),
              ('GL', 'Groenland', 'Amérique du Nord', 'DKK', 'C,E,K', 'UTC-3 à UTC+0', 'Nuuk'),
              ('JE', 'Jersey', 'Europe', 'GBP', 'G', 'UTC+0', 'Saint Helier'),
              ('GG', 'Guernesey', 'Europe', 'GBP', 'G', 'UTC+0', 'Saint Peter Port'),
              ('IM', 'Île de Man', 'Europe', 'GBP', 'G', 'UTC+0', 'Douglas'),
              ('FK', 'Îles Malouines', 'Amérique du Sud', 'FKP', 'G', 'UTC-3', 'Stanley'),
              ('CK', 'Îles Cook', 'Océanie', 'NZD', 'I', 'UTC-10', 'Avarua')
          ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM currency WHERE code = 'KPW'");

        $this->addSql("DELETE FROM country WHERE code IN (
              'HU','CZ','BY','KG','KP','AG',
              'HK','MO','XK','PS',
              'GP','MQ','GF','RE','YT','NC','PF','PM','BL','MF','WF',
              'AW','CW','KY','BM','VG','TC','PR','GI','FO','GL','JE','GG','IM','FK','CK'
          )");
    }
}
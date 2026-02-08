<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208162805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE country ADD COLUMN plug_types VARCHAR(25) DEFAULT NULL, ADD COLUMN timezone VARCHAR(50) DEFAULT NULL;");
        $this->addSql("-- Mise à jour des colonnes plug_types et timezone pour tous les pays

-- Afghanistan
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+4:30' WHERE code = 'AF';

-- Afrique du Sud
UPDATE country SET plug_types = 'C,D,M,N', timezone = 'UTC+2' WHERE code = 'ZA';

-- Albanie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'AL';

-- Algérie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'DZ';

-- Allemagne
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'DE';

-- Andorre
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'AD';

-- Angola
UPDATE country SET plug_types = 'C', timezone = 'UTC+1' WHERE code = 'AO';

-- Arabie saoudite
UPDATE country SET plug_types = 'G', timezone = 'UTC+3' WHERE code = 'SA';

-- Argentine
UPDATE country SET plug_types = 'C,I', timezone = 'UTC-3' WHERE code = 'AR';

-- Arménie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+4' WHERE code = 'AM';

-- Australie
UPDATE country SET plug_types = 'I', timezone = 'UTC+8 à UTC+11' WHERE code = 'AU';

-- Autriche
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'AT';

-- Azerbaïdjan
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+4' WHERE code = 'AZ';

-- Belgique
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'BE';

-- Bénin
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'BJ';

-- Bolivie
UPDATE country SET plug_types = 'A,C', timezone = 'UTC-4' WHERE code = 'BO';

-- Bosnie-Herzégovine
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'BA';

-- Botswana
UPDATE country SET plug_types = 'D,G,M', timezone = 'UTC+2' WHERE code = 'BW';

-- Brésil
UPDATE country SET plug_types = 'C,N', timezone = 'UTC-2 à UTC-5' WHERE code = 'BR';

-- Bulgarie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2' WHERE code = 'BG';

-- Burkina Faso
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+0' WHERE code = 'BF';

-- Burundi
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+2' WHERE code = 'BI';

-- Cambodge
UPDATE country SET plug_types = 'A,C,G', timezone = 'UTC+7' WHERE code = 'KH';

-- Cameroun
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'CM';

-- Canada
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-3:30 à UTC-8' WHERE code = 'CA';

-- Chili
UPDATE country SET plug_types = 'C,L', timezone = 'UTC-3 à UTC-6' WHERE code = 'CL';

-- Chine
UPDATE country SET plug_types = 'A,C,I', timezone = 'UTC+8' WHERE code = 'CN';

-- Chypre
UPDATE country SET plug_types = 'G', timezone = 'UTC+2' WHERE code = 'CY';

-- Colombie
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-5' WHERE code = 'CO';

-- Corée du Sud
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+9' WHERE code = 'KR';

-- Costa Rica
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-6' WHERE code = 'CR';

-- Croatie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'HR';

-- Cuba
UPDATE country SET plug_types = 'A,B,C,L', timezone = 'UTC-5' WHERE code = 'CU';

-- Danemark
UPDATE country SET plug_types = 'C,E,F,K', timezone = 'UTC+1' WHERE code = 'DK';

-- Égypte
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2' WHERE code = 'EG';

-- Émirats arabes unis
UPDATE country SET plug_types = 'C,D,G', timezone = 'UTC+4' WHERE code = 'AE';

-- Équateur
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-5 à UTC-6' WHERE code = 'EC';

-- Espagne
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'ES';

-- Estonie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2' WHERE code = 'EE';

-- États-Unis
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-5 à UTC-10' WHERE code = 'US';

-- Finlande
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2' WHERE code = 'FI';

-- France
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'FR';

-- Grèce
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2' WHERE code = 'GR';

-- Inde
UPDATE country SET plug_types = 'C,D,M', timezone = 'UTC+5:30' WHERE code = 'IN';

-- Indonésie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+7 à UTC+9' WHERE code = 'ID';

-- Iran
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+3:30' WHERE code = 'IR';

-- Irak
UPDATE country SET plug_types = 'C,D,G', timezone = 'UTC+3' WHERE code = 'IQ';

-- Irlande
UPDATE country SET plug_types = 'G', timezone = 'UTC+0' WHERE code = 'IE';

-- Islande
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+0' WHERE code = 'IS';

-- Israël
UPDATE country SET plug_types = 'C,H,M', timezone = 'UTC+2' WHERE code = 'IL';

-- Italie
UPDATE country SET plug_types = 'C,F,L', timezone = 'UTC+1' WHERE code = 'IT';

-- Japon
UPDATE country SET plug_types = 'A,B', timezone = 'UTC+9' WHERE code = 'JP';

-- Jordanie
UPDATE country SET plug_types = 'C,D,F,G,J', timezone = 'UTC+3' WHERE code = 'JO';

-- Kazakhstan
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+5 à UTC+6' WHERE code = 'KZ';

-- Kenya
UPDATE country SET plug_types = 'G', timezone = 'UTC+3' WHERE code = 'KE';

-- Koweït
UPDATE country SET plug_types = 'C,D,G', timezone = 'UTC+3' WHERE code = 'KW';

-- Lettonie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2' WHERE code = 'LV';

-- Liban
UPDATE country SET plug_types = 'A,B,C,D,G', timezone = 'UTC+2' WHERE code = 'LB';

-- Libye
UPDATE country SET plug_types = 'C,D,F,L', timezone = 'UTC+2' WHERE code = 'LY';

-- Lituanie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2' WHERE code = 'LT';

-- Luxembourg
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'LU';

-- Madagascar
UPDATE country SET plug_types = 'C,D,E,J,K', timezone = 'UTC+3' WHERE code = 'MG';

-- Malaisie
UPDATE country SET plug_types = 'G', timezone = 'UTC+8' WHERE code = 'MY';

-- Mali
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+0' WHERE code = 'ML';

-- Maroc
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'MA';

-- Mexique
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-5 à UTC-8' WHERE code = 'MX';

-- Monaco
UPDATE country SET plug_types = 'C,D,E,F', timezone = 'UTC+1' WHERE code = 'MC';

-- Mongolie
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+7 à UTC+8' WHERE code = 'MN';

-- Monténégro
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'ME';

-- Mozambique
UPDATE country SET plug_types = 'C,F,M', timezone = 'UTC+2' WHERE code = 'MZ';

-- Namibie
UPDATE country SET plug_types = 'D,M', timezone = 'UTC+2' WHERE code = 'NA';

-- Népal
UPDATE country SET plug_types = 'C,D,M', timezone = 'UTC+5:45' WHERE code = 'NP';

-- Nicaragua
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-6' WHERE code = 'NI';

-- Niger
UPDATE country SET plug_types = 'C,D,E,F', timezone = 'UTC+1' WHERE code = 'NE';

-- Nigéria
UPDATE country SET plug_types = 'D,G', timezone = 'UTC+1' WHERE code = 'NG';

-- Norvège
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'NO';

-- Nouvelle-Zélande
UPDATE country SET plug_types = 'I', timezone = 'UTC+12 à UTC+13' WHERE code = 'NZ';

-- Ouganda
UPDATE country SET plug_types = 'G', timezone = 'UTC+3' WHERE code = 'UG';

-- Ouzbékistan
UPDATE country SET plug_types = 'C,I', timezone = 'UTC+5' WHERE code = 'UZ';

-- Pakistan
UPDATE country SET plug_types = 'C,D', timezone = 'UTC+5' WHERE code = 'PK';

-- Panama
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-5' WHERE code = 'PA';

-- Paraguay
UPDATE country SET plug_types = 'C', timezone = 'UTC-4' WHERE code = 'PY';

-- Pays-Bas
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'NL';

-- Pérou
UPDATE country SET plug_types = 'A,B,C', timezone = 'UTC-5' WHERE code = 'PE';

-- Philippines
UPDATE country SET plug_types = 'A,B,C', timezone = 'UTC+8' WHERE code = 'PH';

-- Pologne
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'PL';

-- Portugal
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+0' WHERE code = 'PT';

-- Qatar
UPDATE country SET plug_types = 'D,G', timezone = 'UTC+3' WHERE code = 'QA';

-- Roumanie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2' WHERE code = 'RO';

-- Russie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2 à UTC+12' WHERE code = 'RU';

-- Rwanda
UPDATE country SET plug_types = 'C,J', timezone = 'UTC+2' WHERE code = 'RW';

-- Sénégal
UPDATE country SET plug_types = 'C,D,E,K', timezone = 'UTC+0' WHERE code = 'SN';

-- Serbie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'RS';

-- Singapour
UPDATE country SET plug_types = 'G', timezone = 'UTC+8' WHERE code = 'SG';

-- Slovaquie
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'SK';

-- Slovénie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'SI';

-- Somalie
UPDATE country SET plug_types = 'C', timezone = 'UTC+3' WHERE code = 'SO';

-- Soudan
UPDATE country SET plug_types = 'C,D', timezone = 'UTC+2' WHERE code = 'SD';

-- Suède
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'SE';

-- Suisse
UPDATE country SET plug_types = 'C,J', timezone = 'UTC+1' WHERE code = 'CH';

-- Syrie
UPDATE country SET plug_types = 'C,E,L', timezone = 'UTC+3' WHERE code = 'SY';

-- Taïwan
UPDATE country SET plug_types = 'A,B', timezone = 'UTC+8' WHERE code = 'TW';

-- Tanzanie
UPDATE country SET plug_types = 'D,G', timezone = 'UTC+3' WHERE code = 'TZ';

-- Thaïlande
UPDATE country SET plug_types = 'A,B,C,O', timezone = 'UTC+7' WHERE code = 'TH';

-- Tunisie
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'TN';

-- Turquie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+3' WHERE code = 'TR';

-- Ukraine
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2' WHERE code = 'UA';

-- Royaume-Uni
UPDATE country SET plug_types = 'G', timezone = 'UTC+0' WHERE code = 'GB';

-- Venezuela
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-4' WHERE code = 'VE';

-- Vietnam
UPDATE country SET plug_types = 'A,C,G', timezone = 'UTC+7' WHERE code = 'VN';

-- Yémen
UPDATE country SET plug_types = 'A,D,G', timezone = 'UTC+3' WHERE code = 'YE';

-- Zambie
UPDATE country SET plug_types = 'C,D,G', timezone = 'UTC+2' WHERE code = 'ZM';

-- Zimbabwe
UPDATE country SET plug_types = 'D,G', timezone = 'UTC+2' WHERE code = 'ZW';

-- Bahamas
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-5' WHERE code = 'BS';

-- Barbade
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-4' WHERE code = 'BB';

-- Belize
UPDATE country SET plug_types = 'A,B,G', timezone = 'UTC-6' WHERE code = 'BZ';

-- Dominique
UPDATE country SET plug_types = 'D,G', timezone = 'UTC-4' WHERE code = 'DM';

-- El Salvador
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-6' WHERE code = 'SV';

-- Grenade
UPDATE country SET plug_types = 'G', timezone = 'UTC-4' WHERE code = 'GD';

-- Guatemala
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-6' WHERE code = 'GT';

-- Haïti
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-5' WHERE code = 'HT';

-- Honduras
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-6' WHERE code = 'HN';

-- Jamaïque
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-5' WHERE code = 'JM';

-- Saint-Kitts-et-Nevis
UPDATE country SET plug_types = 'A,B,D,G', timezone = 'UTC-4' WHERE code = 'KN';

-- Sainte-Lucie
UPDATE country SET plug_types = 'G', timezone = 'UTC-4' WHERE code = 'LC';

-- Saint-Vincent-et-les-Grenadines
UPDATE country SET plug_types = 'A,C,E,G,I,K', timezone = 'UTC-4' WHERE code = 'VC';

-- République dominicaine
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-4' WHERE code = 'DO';

-- Trinité-et-Tobago
UPDATE country SET plug_types = 'A,B', timezone = 'UTC-4' WHERE code = 'TT';

-- Guyana
UPDATE country SET plug_types = 'A,B,D,G', timezone = 'UTC-4' WHERE code = 'GY';

-- Suriname
UPDATE country SET plug_types = 'C,F', timezone = 'UTC-3' WHERE code = 'SR';

-- Uruguay
UPDATE country SET plug_types = 'C,F,I,L', timezone = 'UTC-3' WHERE code = 'UY';

-- Liechtenstein
UPDATE country SET plug_types = 'C,J', timezone = 'UTC+1' WHERE code = 'LI';

-- Macédoine du Nord
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+1' WHERE code = 'MK';

-- Malte
UPDATE country SET plug_types = 'G', timezone = 'UTC+1' WHERE code = 'MT';

-- Moldavie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+2' WHERE code = 'MD';

-- Saint-Marin
UPDATE country SET plug_types = 'C,F,L', timezone = 'UTC+1' WHERE code = 'SM';

-- Vatican
UPDATE country SET plug_types = 'C,F,L', timezone = 'UTC+1' WHERE code = 'VA';

-- Bahreïn
UPDATE country SET plug_types = 'G', timezone = 'UTC+3' WHERE code = 'BH';

-- Bangladesh
UPDATE country SET plug_types = 'C,D,G,K', timezone = 'UTC+6' WHERE code = 'BD';

-- Bhoutan
UPDATE country SET plug_types = 'C,D,F,G,M', timezone = 'UTC+6' WHERE code = 'BT';

-- Brunei
UPDATE country SET plug_types = 'G', timezone = 'UTC+8' WHERE code = 'BN';

-- Géorgie
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+4' WHERE code = 'GE';

-- Laos
UPDATE country SET plug_types = 'A,B,C,E,F', timezone = 'UTC+7' WHERE code = 'LA';

-- Maldives
UPDATE country SET plug_types = 'A,C,D,G,J,K,L', timezone = 'UTC+5' WHERE code = 'MV';

-- Myanmar
UPDATE country SET plug_types = 'C,D,F,G', timezone = 'UTC+6:30' WHERE code = 'MM';

-- Oman
UPDATE country SET plug_types = 'C,G', timezone = 'UTC+4' WHERE code = 'OM';

-- Sri Lanka
UPDATE country SET plug_types = 'D,G,M', timezone = 'UTC+5:30' WHERE code = 'LK';

-- Tadjikistan
UPDATE country SET plug_types = 'C,F,I', timezone = 'UTC+5' WHERE code = 'TJ';

-- Timor oriental
UPDATE country SET plug_types = 'C,E,F,I', timezone = 'UTC+9' WHERE code = 'TL';

-- Turkménistan
UPDATE country SET plug_types = 'B,C,F', timezone = 'UTC+5' WHERE code = 'TM';

-- Cabo Verde
UPDATE country SET plug_types = 'C,F', timezone = 'UTC-1' WHERE code = 'CV';

-- Comores
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+3' WHERE code = 'KM';

-- Congo
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'CG';

-- RD Congo
UPDATE country SET plug_types = 'C,D', timezone = 'UTC+1 à UTC+2' WHERE code = 'CD';

-- Côte d'Ivoire
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+0' WHERE code = 'CI';

-- Djibouti
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+3' WHERE code = 'DJ';

-- Érythrée
UPDATE country SET plug_types = 'C,L', timezone = 'UTC+3' WHERE code = 'ER';

-- Eswatini
UPDATE country SET plug_types = 'M', timezone = 'UTC+2' WHERE code = 'SZ';

-- Éthiopie
UPDATE country SET plug_types = 'C,E,F,L', timezone = 'UTC+3' WHERE code = 'ET';

-- Gabon
UPDATE country SET plug_types = 'C', timezone = 'UTC+1' WHERE code = 'GA';

-- Gambie
UPDATE country SET plug_types = 'G', timezone = 'UTC+0' WHERE code = 'GM';

-- Ghana
UPDATE country SET plug_types = 'D,G', timezone = 'UTC+0' WHERE code = 'GH';

-- Guinée
UPDATE country SET plug_types = 'C,F,K', timezone = 'UTC+0' WHERE code = 'GN';

-- Guinée-Bissau
UPDATE country SET plug_types = 'C', timezone = 'UTC+0' WHERE code = 'GW';

-- Guinée équatoriale
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'GQ';

-- Lesotho
UPDATE country SET plug_types = 'M', timezone = 'UTC+2' WHERE code = 'LS';

-- Liberia
UPDATE country SET plug_types = 'A,B,C,E,F', timezone = 'UTC+0' WHERE code = 'LR';

-- Malawi
UPDATE country SET plug_types = 'G', timezone = 'UTC+2' WHERE code = 'MW';

-- Maurice
UPDATE country SET plug_types = 'C,G', timezone = 'UTC+4' WHERE code = 'MU';

-- Mauritanie
UPDATE country SET plug_types = 'C', timezone = 'UTC+0' WHERE code = 'MR';

-- République centrafricaine
UPDATE country SET plug_types = 'C,E', timezone = 'UTC+1' WHERE code = 'CF';

-- São Tomé-et-Príncipe
UPDATE country SET plug_types = 'C,F', timezone = 'UTC+0' WHERE code = 'ST';

-- Seychelles
UPDATE country SET plug_types = 'G', timezone = 'UTC+4' WHERE code = 'SC';

-- Sierra Leone
UPDATE country SET plug_types = 'D,G', timezone = 'UTC+0' WHERE code = 'SL';

-- Soudan du Sud
UPDATE country SET plug_types = 'C,D', timezone = 'UTC+2' WHERE code = 'SS';

-- Tchad
UPDATE country SET plug_types = 'C,D,E,F', timezone = 'UTC+1' WHERE code = 'TD';

-- Togo
UPDATE country SET plug_types = 'C', timezone = 'UTC+0' WHERE code = 'TG';

-- Fidji
UPDATE country SET plug_types = 'I', timezone = 'UTC+12' WHERE code = 'FJ';

-- Îles Marshall
UPDATE country SET plug_types = 'A,B', timezone = 'UTC+12' WHERE code = 'MH';

-- Îles Salomon
UPDATE country SET plug_types = 'G,I', timezone = 'UTC+11' WHERE code = 'SB';

-- Kiribati
UPDATE country SET plug_types = 'I', timezone = 'UTC+12 à UTC+14' WHERE code = 'KI';

-- Micronésie
UPDATE country SET plug_types = 'A,B', timezone = 'UTC+10 à UTC+11' WHERE code = 'FM';

-- Nauru
UPDATE country SET plug_types = 'I', timezone = 'UTC+12' WHERE code = 'NR';

-- Palaos
UPDATE country SET plug_types = 'A,B', timezone = 'UTC+9' WHERE code = 'PW';

-- Papouasie-Nouvelle-Guinée
UPDATE country SET plug_types = 'I', timezone = 'UTC+10' WHERE code = 'PG';

-- Samoa
UPDATE country SET plug_types = 'I', timezone = 'UTC+13' WHERE code = 'WS';

-- Tonga
UPDATE country SET plug_types = 'I', timezone = 'UTC+13' WHERE code = 'TO';

-- Tuvalu
UPDATE country SET plug_types = 'I', timezone = 'UTC+12' WHERE code = 'TV';

-- Vanuatu
UPDATE country SET plug_types = 'C,G,I', timezone = 'UTC+11' WHERE code = 'VU';");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}

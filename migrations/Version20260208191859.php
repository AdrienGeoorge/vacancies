<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208191859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE country ADD COLUMN capital VARCHAR(100) DEFAULT NULL;");
        $this->addSql("UPDATE country SET capital = 'Kabul' WHERE code = 'AF';
UPDATE country SET capital = 'Pretoria' WHERE code = 'ZA';
UPDATE country SET capital = 'Tirana' WHERE code = 'AL';
UPDATE country SET capital = 'Algiers' WHERE code = 'DZ';
UPDATE country SET capital = 'Berlin' WHERE code = 'DE';
UPDATE country SET capital = 'Andorra la Vella' WHERE code = 'AD';
UPDATE country SET capital = 'Luanda' WHERE code = 'AO';
UPDATE country SET capital = 'Riyadh' WHERE code = 'SA';
UPDATE country SET capital = 'Buenos Aires' WHERE code = 'AR';
UPDATE country SET capital = 'Yerevan' WHERE code = 'AM';
UPDATE country SET capital = 'Canberra' WHERE code = 'AU';
UPDATE country SET capital = 'Vienna' WHERE code = 'AT';
UPDATE country SET capital = 'Baku' WHERE code = 'AZ';
UPDATE country SET capital = 'Brussels' WHERE code = 'BE';
UPDATE country SET capital = 'Porto-Novo' WHERE code = 'BJ';
UPDATE country SET capital = 'La Paz' WHERE code = 'BO';
UPDATE country SET capital = 'Sarajevo' WHERE code = 'BA';
UPDATE country SET capital = 'Gaborone' WHERE code = 'BW';
UPDATE country SET capital = 'Brasilia' WHERE code = 'BR';
UPDATE country SET capital = 'Sofia' WHERE code = 'BG';
UPDATE country SET capital = 'Ouagadougou' WHERE code = 'BF';
UPDATE country SET capital = 'Gitega' WHERE code = 'BI';
UPDATE country SET capital = 'Phnom Penh' WHERE code = 'KH';
UPDATE country SET capital = 'Yaounde' WHERE code = 'CM';
UPDATE country SET capital = 'Ottawa' WHERE code = 'CA';
UPDATE country SET capital = 'Santiago' WHERE code = 'CL';
UPDATE country SET capital = 'Beijing' WHERE code = 'CN';
UPDATE country SET capital = 'Nicosia' WHERE code = 'CY';
UPDATE country SET capital = 'Bogota' WHERE code = 'CO';
UPDATE country SET capital = 'Seoul' WHERE code = 'KR';
UPDATE country SET capital = 'San Jose' WHERE code = 'CR';
UPDATE country SET capital = 'Zagreb' WHERE code = 'HR';
UPDATE country SET capital = 'Havana' WHERE code = 'CU';
UPDATE country SET capital = 'Copenhagen' WHERE code = 'DK';
UPDATE country SET capital = 'Cairo' WHERE code = 'EG';
UPDATE country SET capital = 'Abu Dhabi' WHERE code = 'AE';
UPDATE country SET capital = 'Quito' WHERE code = 'EC';
UPDATE country SET capital = 'Madrid' WHERE code = 'ES';
UPDATE country SET capital = 'Tallinn' WHERE code = 'EE';
UPDATE country SET capital = 'Washington' WHERE code = 'US';
UPDATE country SET capital = 'Helsinki' WHERE code = 'FI';
UPDATE country SET capital = 'Paris' WHERE code = 'FR';
UPDATE country SET capital = 'Athens' WHERE code = 'GR';
UPDATE country SET capital = 'New Delhi' WHERE code = 'IN';
UPDATE country SET capital = 'Jakarta' WHERE code = 'ID';
UPDATE country SET capital = 'Tehran' WHERE code = 'IR';
UPDATE country SET capital = 'Baghdad' WHERE code = 'IQ';
UPDATE country SET capital = 'Dublin' WHERE code = 'IE';
UPDATE country SET capital = 'Reykjavik' WHERE code = 'IS';
UPDATE country SET capital = 'Jerusalem' WHERE code = 'IL';
UPDATE country SET capital = 'Rome' WHERE code = 'IT';
UPDATE country SET capital = 'Tokyo' WHERE code = 'JP';
UPDATE country SET capital = 'Amman' WHERE code = 'JO';
UPDATE country SET capital = 'Nur-Sultan' WHERE code = 'KZ';
UPDATE country SET capital = 'Nairobi' WHERE code = 'KE';
UPDATE country SET capital = 'Kuwait City' WHERE code = 'KW';
UPDATE country SET capital = 'Riga' WHERE code = 'LV';
UPDATE country SET capital = 'Beirut' WHERE code = 'LB';
UPDATE country SET capital = 'Tripoli' WHERE code = 'LY';
UPDATE country SET capital = 'Vilnius' WHERE code = 'LT';
UPDATE country SET capital = 'Luxembourg' WHERE code = 'LU';
UPDATE country SET capital = 'Antananarivo' WHERE code = 'MG';
UPDATE country SET capital = 'Kuala Lumpur' WHERE code = 'MY';
UPDATE country SET capital = 'Bamako' WHERE code = 'ML';
UPDATE country SET capital = 'Rabat' WHERE code = 'MA';
UPDATE country SET capital = 'Mexico City' WHERE code = 'MX';
UPDATE country SET capital = 'Monaco' WHERE code = 'MC';
UPDATE country SET capital = 'Ulaanbaatar' WHERE code = 'MN';
UPDATE country SET capital = 'Podgorica' WHERE code = 'ME';
UPDATE country SET capital = 'Maputo' WHERE code = 'MZ';
UPDATE country SET capital = 'Windhoek' WHERE code = 'NA';
UPDATE country SET capital = 'Kathmandu' WHERE code = 'NP';
UPDATE country SET capital = 'Managua' WHERE code = 'NI';
UPDATE country SET capital = 'Niamey' WHERE code = 'NE';
UPDATE country SET capital = 'Abuja' WHERE code = 'NG';
UPDATE country SET capital = 'Oslo' WHERE code = 'NO';
UPDATE country SET capital = 'Wellington' WHERE code = 'NZ';
UPDATE country SET capital = 'Kampala' WHERE code = 'UG';
UPDATE country SET capital = 'Tashkent' WHERE code = 'UZ';
UPDATE country SET capital = 'Islamabad' WHERE code = 'PK';
UPDATE country SET capital = 'Panama City' WHERE code = 'PA';
UPDATE country SET capital = 'Asuncion' WHERE code = 'PY';
UPDATE country SET capital = 'Amsterdam' WHERE code = 'NL';
UPDATE country SET capital = 'Lima' WHERE code = 'PE';
UPDATE country SET capital = 'Manila' WHERE code = 'PH';
UPDATE country SET capital = 'Warsaw' WHERE code = 'PL';
UPDATE country SET capital = 'Lisbon' WHERE code = 'PT';
UPDATE country SET capital = 'Doha' WHERE code = 'QA';
UPDATE country SET capital = 'Bucharest' WHERE code = 'RO';
UPDATE country SET capital = 'Moscow' WHERE code = 'RU';
UPDATE country SET capital = 'Kigali' WHERE code = 'RW';
UPDATE country SET capital = 'Dakar' WHERE code = 'SN';
UPDATE country SET capital = 'Belgrade' WHERE code = 'RS';
UPDATE country SET capital = 'Singapore' WHERE code = 'SG';
UPDATE country SET capital = 'Bratislava' WHERE code = 'SK';
UPDATE country SET capital = 'Ljubljana' WHERE code = 'SI';
UPDATE country SET capital = 'Mogadishu' WHERE code = 'SO';
UPDATE country SET capital = 'Khartoum' WHERE code = 'SD';
UPDATE country SET capital = 'Stockholm' WHERE code = 'SE';
UPDATE country SET capital = 'Bern' WHERE code = 'CH';
UPDATE country SET capital = 'Damascus' WHERE code = 'SY';
UPDATE country SET capital = 'Taipei' WHERE code = 'TW';
UPDATE country SET capital = 'Dodoma' WHERE code = 'TZ';
UPDATE country SET capital = 'Bangkok' WHERE code = 'TH';
UPDATE country SET capital = 'Tunis' WHERE code = 'TN';
UPDATE country SET capital = 'Ankara' WHERE code = 'TR';
UPDATE country SET capital = 'Kyiv' WHERE code = 'UA';
UPDATE country SET capital = 'London' WHERE code = 'GB';
UPDATE country SET capital = 'Caracas' WHERE code = 'VE';
UPDATE country SET capital = 'Hanoi' WHERE code = 'VN';
UPDATE country SET capital = 'Sanaa' WHERE code = 'YE';
UPDATE country SET capital = 'Lusaka' WHERE code = 'ZM';
UPDATE country SET capital = 'Harare' WHERE code = 'ZW';
UPDATE country SET capital = 'Nassau' WHERE code = 'BS';
UPDATE country SET capital = 'Bridgetown' WHERE code = 'BB';
UPDATE country SET capital = 'Belmopan' WHERE code = 'BZ';
UPDATE country SET capital = 'Roseau' WHERE code = 'DM';
UPDATE country SET capital = 'San Salvador' WHERE code = 'SV';
UPDATE country SET capital = 'St. Georges' WHERE code = 'GD';
UPDATE country SET capital = 'Guatemala City' WHERE code = 'GT';
UPDATE country SET capital = 'Port-au-Prince' WHERE code = 'HT';
UPDATE country SET capital = 'Tegucigalpa' WHERE code = 'HN';
UPDATE country SET capital = 'Kingston' WHERE code = 'JM';
UPDATE country SET capital = 'Basseterre' WHERE code = 'KN';
UPDATE country SET capital = 'Castries' WHERE code = 'LC';
UPDATE country SET capital = 'Kingstown' WHERE code = 'VC';
UPDATE country SET capital = 'Santo Domingo' WHERE code = 'DO';
UPDATE country SET capital = 'Port of Spain' WHERE code = 'TT';
UPDATE country SET capital = 'Georgetown' WHERE code = 'GY';
UPDATE country SET capital = 'Paramaribo' WHERE code = 'SR';
UPDATE country SET capital = 'Montevideo' WHERE code = 'UY';
UPDATE country SET capital = 'Vaduz' WHERE code = 'LI';
UPDATE country SET capital = 'Skopje' WHERE code = 'MK';
UPDATE country SET capital = 'Valletta' WHERE code = 'MT';
UPDATE country SET capital = 'Chisinau' WHERE code = 'MD';
UPDATE country SET capital = 'San Marino' WHERE code = 'SM';
UPDATE country SET capital = 'Vatican City' WHERE code = 'VA';
UPDATE country SET capital = 'Manama' WHERE code = 'BH';
UPDATE country SET capital = 'Dhaka' WHERE code = 'BD';
UPDATE country SET capital = 'Thimphu' WHERE code = 'BT';
UPDATE country SET capital = 'Bandar Seri Begawan' WHERE code = 'BN';
UPDATE country SET capital = 'Tbilisi' WHERE code = 'GE';
UPDATE country SET capital = 'Vientiane' WHERE code = 'LA';
UPDATE country SET capital = 'Male' WHERE code = 'MV';
UPDATE country SET capital = 'Naypyidaw' WHERE code = 'MM';
UPDATE country SET capital = 'Muscat' WHERE code = 'OM';
UPDATE country SET capital = 'Colombo' WHERE code = 'LK';
UPDATE country SET capital = 'Dushanbe' WHERE code = 'TJ';
UPDATE country SET capital = 'Dili' WHERE code = 'TL';
UPDATE country SET capital = 'Ashgabat' WHERE code = 'TM';
UPDATE country SET capital = 'Praia' WHERE code = 'CV';
UPDATE country SET capital = 'Moroni' WHERE code = 'KM';
UPDATE country SET capital = 'Brazzaville' WHERE code = 'CG';
UPDATE country SET capital = 'Kinshasa' WHERE code = 'CD';
UPDATE country SET capital = 'Yamoussoukro' WHERE code = 'CI';
UPDATE country SET capital = 'Djibouti' WHERE code = 'DJ';
UPDATE country SET capital = 'Asmara' WHERE code = 'ER';
UPDATE country SET capital = 'Mbabane' WHERE code = 'SZ';
UPDATE country SET capital = 'Addis Ababa' WHERE code = 'ET';
UPDATE country SET capital = 'Libreville' WHERE code = 'GA';
UPDATE country SET capital = 'Banjul' WHERE code = 'GM';
UPDATE country SET capital = 'Accra' WHERE code = 'GH';
UPDATE country SET capital = 'Conakry' WHERE code = 'GN';
UPDATE country SET capital = 'Bissau' WHERE code = 'GW';
UPDATE country SET capital = 'Malabo' WHERE code = 'GQ';
UPDATE country SET capital = 'Maseru' WHERE code = 'LS';
UPDATE country SET capital = 'Monrovia' WHERE code = 'LR';
UPDATE country SET capital = 'Lilongwe' WHERE code = 'MW';
UPDATE country SET capital = 'Port Louis' WHERE code = 'MU';
UPDATE country SET capital = 'Nouakchott' WHERE code = 'MR';
UPDATE country SET capital = 'Bangui' WHERE code = 'CF';
UPDATE country SET capital = 'Sao Tome' WHERE code = 'ST';
UPDATE country SET capital = 'Victoria' WHERE code = 'SC';
UPDATE country SET capital = 'Freetown' WHERE code = 'SL';
UPDATE country SET capital = 'Juba' WHERE code = 'SS';
UPDATE country SET capital = 'N\'Djamena' WHERE code = 'TD';
UPDATE country SET capital = 'Lome' WHERE code = 'TG';
UPDATE country SET capital = 'Suva' WHERE code = 'FJ';
UPDATE country SET capital = 'Majuro' WHERE code = 'MH';
UPDATE country SET capital = 'Honiara' WHERE code = 'SB';
UPDATE country SET capital = 'Tarawa' WHERE code = 'KI';
UPDATE country SET capital = 'Palikir' WHERE code = 'FM';
UPDATE country SET capital = 'Yaren' WHERE code = 'NR';
UPDATE country SET capital = 'Ngerulmud' WHERE code = 'PW';
UPDATE country SET capital = 'Port Moresby' WHERE code = 'PG';
UPDATE country SET capital = 'Apia' WHERE code = 'WS';
UPDATE country SET capital = 'Nuku\'alofa' WHERE code = 'TO';
UPDATE country SET capital = 'Funafuti' WHERE code = 'TV';
UPDATE country SET capital = 'Port Vila' WHERE code = 'VU';");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}

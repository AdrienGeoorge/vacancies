<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111173505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE country SET currency = 'AFN' WHERE code = 'AF'; -- Afghanistan - Afghani
UPDATE country SET currency = 'ZAR' WHERE code = 'ZA'; -- Afrique du Sud - Rand
UPDATE country SET currency = 'ALL' WHERE code = 'AL'; -- Albanie - Lek
UPDATE country SET currency = 'DZD' WHERE code = 'DZ'; -- Algérie - Dinar algérien
UPDATE country SET currency = 'EUR' WHERE code = 'DE'; -- Allemagne - Euro
UPDATE country SET currency = 'EUR' WHERE code = 'AD'; -- Andorre - Euro
UPDATE country SET currency = 'AOA' WHERE code = 'AO'; -- Angola - Kwanza
UPDATE country SET currency = 'SAR' WHERE code = 'SA'; -- Arabie saoudite - Riyal
UPDATE country SET currency = 'ARS' WHERE code = 'AR'; -- Argentine - Peso argentin
UPDATE country SET currency = 'AMD' WHERE code = 'AM'; -- Arménie - Dram
UPDATE country SET currency = 'AUD' WHERE code = 'AU'; -- Australie - Dollar australien
UPDATE country SET currency = 'EUR' WHERE code = 'AT'; -- Autriche - Euro
UPDATE country SET currency = 'AZN' WHERE code = 'AZ'; -- Azerbaïdjan - Manat
UPDATE country SET currency = 'EUR' WHERE code = 'BE'; -- Belgique - Euro
UPDATE country SET currency = 'XOF' WHERE code = 'BJ'; -- Bénin - Franc CFA
UPDATE country SET currency = 'BOB' WHERE code = 'BO'; -- Bolivie - Boliviano
UPDATE country SET currency = 'BAM' WHERE code = 'BA'; -- Bosnie-Herzégovine - Mark convertible
UPDATE country SET currency = 'BWP' WHERE code = 'BW'; -- Botswana - Pula
UPDATE country SET currency = 'BRL' WHERE code = 'BR'; -- Brésil - Real
UPDATE country SET currency = 'BGN' WHERE code = 'BG'; -- Bulgarie - Lev
UPDATE country SET currency = 'XOF' WHERE code = 'BF'; -- Burkina Faso - Franc CFA
UPDATE country SET currency = 'BIF' WHERE code = 'BI'; -- Burundi - Franc burundais
UPDATE country SET currency = 'KHR' WHERE code = 'KH'; -- Cambodge - Riel
UPDATE country SET currency = 'XAF' WHERE code = 'CM'; -- Cameroun - Franc CFA
UPDATE country SET currency = 'CAD' WHERE code = 'CA'; -- Canada - Dollar canadien
UPDATE country SET currency = 'CLP' WHERE code = 'CL'; -- Chili - Peso chilien
UPDATE country SET currency = 'CNY' WHERE code = 'CN'; -- Chine - Yuan
UPDATE country SET currency = 'EUR' WHERE code = 'CY'; -- Chypre - Euro
UPDATE country SET currency = 'COP' WHERE code = 'CO'; -- Colombie - Peso colombien
UPDATE country SET currency = 'KRW' WHERE code = 'KR'; -- Corée du Sud - Won
UPDATE country SET currency = 'CRC' WHERE code = 'CR'; -- Costa Rica - Colón
UPDATE country SET currency = 'EUR' WHERE code = 'HR'; -- Croatie - Euro (depuis 2023)
UPDATE country SET currency = 'CUP' WHERE code = 'CU'; -- Cuba - Peso cubain
UPDATE country SET currency = 'DKK' WHERE code = 'DK'; -- Danemark - Couronne danoise
UPDATE country SET currency = 'EGP' WHERE code = 'EG'; -- Égypte - Livre égyptienne
UPDATE country SET currency = 'AED' WHERE code = 'AE'; -- Émirats arabes unis - Dirham
UPDATE country SET currency = 'USD' WHERE code = 'EC'; -- Équateur - Dollar américain
UPDATE country SET currency = 'EUR' WHERE code = 'ES'; -- Espagne - Euro
UPDATE country SET currency = 'EUR' WHERE code = 'EE'; -- Estonie - Euro
UPDATE country SET currency = 'USD' WHERE code = 'US'; -- États-Unis - Dollar américain
UPDATE country SET currency = 'EUR' WHERE code = 'FI'; -- Finlande - Euro
UPDATE country SET currency = 'EUR' WHERE code = 'FR'; -- France - Euro
UPDATE country SET currency = 'EUR' WHERE code = 'GR'; -- Grèce - Euro
UPDATE country SET currency = 'INR' WHERE code = 'IN'; -- Inde - Roupie indienne
UPDATE country SET currency = 'IDR' WHERE code = 'ID'; -- Indonésie - Rupiah
UPDATE country SET currency = 'IRR' WHERE code = 'IR'; -- Iran - Rial
UPDATE country SET currency = 'IQD' WHERE code = 'IQ'; -- Irak - Dinar irakien
UPDATE country SET currency = 'EUR' WHERE code = 'IE'; -- Irlande - Euro
UPDATE country SET currency = 'ISK' WHERE code = 'IS'; -- Islande - Couronne islandaise
UPDATE country SET currency = 'ILS' WHERE code = 'IL'; -- Israël - Shekel
UPDATE country SET currency = 'EUR' WHERE code = 'IT'; -- Italie - Euro
UPDATE country SET currency = 'JPY' WHERE code = 'JP'; -- Japon - Yen
UPDATE country SET currency = 'JOD' WHERE code = 'JO'; -- Jordanie - Dinar jordanien
UPDATE country SET currency = 'KZT' WHERE code = 'KZ'; -- Kazakhstan - Tenge
UPDATE country SET currency = 'KES' WHERE code = 'KE'; -- Kenya - Shilling kenyan
UPDATE country SET currency = 'KWD' WHERE code = 'KW'; -- Koweït - Dinar koweïtien
UPDATE country SET currency = 'EUR' WHERE code = 'LV'; -- Lettonie - Euro
UPDATE country SET currency = 'LBP' WHERE code = 'LB'; -- Liban - Livre libanaise
UPDATE country SET currency = 'LYD' WHERE code = 'LY'; -- Libye - Dinar libyen
UPDATE country SET currency = 'EUR' WHERE code = 'LT'; -- Lituanie - Euro
UPDATE country SET currency = 'EUR' WHERE code = 'LU'; -- Luxembourg - Euro
UPDATE country SET currency = 'MGA' WHERE code = 'MG'; -- Madagascar - Ariary
UPDATE country SET currency = 'MYR' WHERE code = 'MY'; -- Malaisie - Ringgit
UPDATE country SET currency = 'XOF' WHERE code = 'ML'; -- Mali - Franc CFA
UPDATE country SET currency = 'MAD' WHERE code = 'MA'; -- Maroc - Dirham marocain
UPDATE country SET currency = 'MXN' WHERE code = 'MX'; -- Mexique - Peso mexicain
UPDATE country SET currency = 'EUR' WHERE code = 'MC'; -- Monaco - Euro
UPDATE country SET currency = 'MNT' WHERE code = 'MN'; -- Mongolie - Tugrik
UPDATE country SET currency = 'EUR' WHERE code = 'ME'; -- Monténégro - Euro
UPDATE country SET currency = 'MZN' WHERE code = 'MZ'; -- Mozambique - Metical
UPDATE country SET currency = 'NAD' WHERE code = 'NA'; -- Namibie - Dollar namibien
UPDATE country SET currency = 'NPR' WHERE code = 'NP'; -- Népal - Roupie népalaise
UPDATE country SET currency = 'NIO' WHERE code = 'NI'; -- Nicaragua - Córdoba
UPDATE country SET currency = 'XOF' WHERE code = 'NE'; -- Niger - Franc CFA
UPDATE country SET currency = 'NGN' WHERE code = 'NG'; -- Nigéria - Naira
UPDATE country SET currency = 'NOK' WHERE code = 'NO'; -- Norvège - Couronne norvégienne
UPDATE country SET currency = 'NZD' WHERE code = 'NZ'; -- Nouvelle-Zélande - Dollar néo-zélandais
UPDATE country SET currency = 'UGX' WHERE code = 'UG'; -- Ouganda - Shilling ougandais
UPDATE country SET currency = 'UZS' WHERE code = 'UZ'; -- Ouzbékistan - Sum
UPDATE country SET currency = 'PKR' WHERE code = 'PK'; -- Pakistan - Roupie pakistanaise
UPDATE country SET currency = 'PAB' WHERE code = 'PA'; -- Panama - Balboa
UPDATE country SET currency = 'PYG' WHERE code = 'PY'; -- Paraguay - Guaraní
UPDATE country SET currency = 'EUR' WHERE code = 'NL'; -- Pays-Bas - Euro
UPDATE country SET currency = 'PEN' WHERE code = 'PE'; -- Pérou - Sol
UPDATE country SET currency = 'PHP' WHERE code = 'PH'; -- Philippines - Peso philippin
UPDATE country SET currency = 'PLN' WHERE code = 'PL'; -- Pologne - Zloty
UPDATE country SET currency = 'EUR' WHERE code = 'PT'; -- Portugal - Euro
UPDATE country SET currency = 'QAR' WHERE code = 'QA'; -- Qatar - Riyal qatarien
UPDATE country SET currency = 'RON' WHERE code = 'RO'; -- Roumanie - Leu
UPDATE country SET currency = 'RUB' WHERE code = 'RU'; -- Russie - Rouble
UPDATE country SET currency = 'RWF' WHERE code = 'RW'; -- Rwanda - Franc rwandais
UPDATE country SET currency = 'XOF' WHERE code = 'SN'; -- Sénégal - Franc CFA
UPDATE country SET currency = 'RSD' WHERE code = 'RS'; -- Serbie - Dinar serbe
UPDATE country SET currency = 'SGD' WHERE code = 'SG'; -- Singapour - Dollar de Singapour
UPDATE country SET currency = 'EUR' WHERE code = 'SK'; -- Slovaquie - Euro
UPDATE country SET currency = 'EUR' WHERE code = 'SI'; -- Slovénie - Euro
UPDATE country SET currency = 'SOS' WHERE code = 'SO'; -- Somalie - Shilling somalien
UPDATE country SET currency = 'SDG' WHERE code = 'SD'; -- Soudan - Livre soudanaise
UPDATE country SET currency = 'SEK' WHERE code = 'SE'; -- Suède - Couronne suédoise
UPDATE country SET currency = 'CHF' WHERE code = 'CH'; -- Suisse - Franc suisse
UPDATE country SET currency = 'SYP' WHERE code = 'SY'; -- Syrie - Livre syrienne
UPDATE country SET currency = 'TWD' WHERE code = 'TW'; -- Taïwan - Dollar de Taïwan
UPDATE country SET currency = 'TZS' WHERE code = 'TZ'; -- Tanzanie - Shilling tanzanien
UPDATE country SET currency = 'THB' WHERE code = 'TH'; -- Thaïlande - Baht
UPDATE country SET currency = 'TND' WHERE code = 'TN'; -- Tunisie - Dinar tunisien
UPDATE country SET currency = 'TRY' WHERE code = 'TR'; -- Turquie - Livre turque
UPDATE country SET currency = 'UAH' WHERE code = 'UA'; -- Ukraine - Hryvnia
UPDATE country SET currency = 'GBP' WHERE code = 'GB'; -- Royaume-Uni - Livre sterling
UPDATE country SET currency = 'VES' WHERE code = 'VE'; -- Venezuela - Bolívar
UPDATE country SET currency = 'VND' WHERE code = 'VN'; -- Vietnam - Dong
UPDATE country SET currency = 'YER' WHERE code = 'YE'; -- Yémen - Rial yéménite
UPDATE country SET currency = 'ZMW' WHERE code = 'ZM'; -- Zambie - Kwacha
UPDATE country SET currency = 'ZWL' WHERE code = 'ZW'; -- Zimbabwe - Dollar du Zimbabwe");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

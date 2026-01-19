-- =====================================================
-- INSERTION DES DEVISES (si elles n'existent pas déjà)
-- =====================================================

INSERT INTO currency (code, name, symbol)
SELECT 'BSD', 'Dollar bahaméen', 'B$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'BSD');

INSERT INTO currency (code, name, symbol)
SELECT 'BBD', 'Dollar barbadien', 'Bds$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'BBD');

INSERT INTO currency (code, name, symbol)
SELECT 'BZD', 'Dollar bélizien', 'BZ$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'BZD');

INSERT INTO currency (code, name, symbol)
SELECT 'XCD', 'Dollar des Caraïbes orientales', 'EC$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'XCD');

INSERT INTO currency (code, name, symbol)
SELECT 'USD', 'Dollar américain', 'US$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'USD');

INSERT INTO currency (code, name, symbol)
SELECT 'GTQ', 'Quetzal guatémaltèque', 'Q'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'GTQ');

INSERT INTO currency (code, name, symbol)
SELECT 'HTG', 'Gourde haïtienne', 'G'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'HTG');

INSERT INTO currency (code, name, symbol)
SELECT 'HNL', 'Lempira hondurien', 'L'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'HNL');

INSERT INTO currency (code, name, symbol)
SELECT 'JMD', 'Dollar jamaïcain', 'J$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'JMD');

INSERT INTO currency (code, name, symbol)
SELECT 'DOP', 'Peso dominicain', 'RD$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'DOP');

INSERT INTO currency (code, name, symbol)
SELECT 'TTD', 'Dollar de Trinité-et-Tobago', 'TT$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'TTD');

INSERT INTO currency (code, name, symbol)
SELECT 'GYD', 'Dollar guyanien', 'G$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'GYD');

INSERT INTO currency (code, name, symbol)
SELECT 'SRD', 'Dollar surinamais', 'Sr$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'SRD');

INSERT INTO currency (code, name, symbol)
SELECT 'UYU', 'Peso uruguayen', '$U'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'UYU');

INSERT INTO currency (code, name, symbol)
SELECT 'EUR', 'Euro', '€'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'EUR');

INSERT INTO currency (code, name, symbol)
SELECT 'CHF', 'Franc suisse', 'CHF'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'CHF');

INSERT INTO currency (code, name, symbol)
SELECT 'MKD', 'Denar macédonien', 'ден'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'MKD');

INSERT INTO currency (code, name, symbol)
SELECT 'MDL', 'Leu moldave', 'L'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'MDL');

INSERT INTO currency (code, name, symbol)
SELECT 'BHD', 'Dinar bahreïni', 'د.ب'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'BHD');

INSERT INTO currency (code, name, symbol)
SELECT 'BDT', 'Taka bangladais', '৳'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'BDT');

INSERT INTO currency (code, name, symbol)
SELECT 'BTN', 'Ngultrum bhoutanais', 'Nu.'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'BTN');

INSERT INTO currency (code, name, symbol)
SELECT 'BND', 'Dollar de Brunei', 'B$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'BND');

INSERT INTO currency (code, name, symbol)
SELECT 'GEL', 'Lari géorgien', '₾'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'GEL');

INSERT INTO currency (code, name, symbol)
SELECT 'LAK', 'Kip laotien', '₭'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'LAK');

INSERT INTO currency (code, name, symbol)
SELECT 'MVR', 'Rufiyaa maldivienne', 'Rf'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'MVR');

INSERT INTO currency (code, name, symbol)
SELECT 'MMK', 'Kyat birman', 'K'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'MMK');

INSERT INTO currency (code, name, symbol)
SELECT 'OMR', 'Rial omanais', 'ر.ع.'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'OMR');

INSERT INTO currency (code, name, symbol)
SELECT 'LKR', 'Roupie srilankaise', 'Rs'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'LKR');

INSERT INTO currency (code, name, symbol)
SELECT 'TJS', 'Somoni tadjik', 'ЅМ'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'TJS');

INSERT INTO currency (code, name, symbol)
SELECT 'TMT', 'Manat turkmène', 'm'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'TMT');

INSERT INTO currency (code, name, symbol)
SELECT 'CVE', 'Escudo cap-verdien', '$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'CVE');

INSERT INTO currency (code, name, symbol)
SELECT 'KMF', 'Franc comorien', 'CF'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'KMF');

INSERT INTO currency (code, name, symbol)
SELECT 'CDF', 'Franc congolais', 'FC'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'CDF');

INSERT INTO currency (code, name, symbol)
SELECT 'XAF', 'Franc CFA (BEAC)', 'FCFA'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'XAF');

INSERT INTO currency (code, name, symbol)
SELECT 'XOF', 'Franc CFA (BCEAO)', 'CFA'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'XOF');

INSERT INTO currency (code, name, symbol)
SELECT 'DJF', 'Franc djiboutien', 'Fdj'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'DJF');

INSERT INTO currency (code, name, symbol)
SELECT 'ERN', 'Nakfa érythréen', 'Nfk'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'ERN');

INSERT INTO currency (code, name, symbol)
SELECT 'SZL', 'Lilangeni swazi', 'E'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'SZL');

INSERT INTO currency (code, name, symbol)
SELECT 'ETB', 'Birr éthiopien', 'Br'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'ETB');

INSERT INTO currency (code, name, symbol)
SELECT 'GMD', 'Dalasi gambien', 'D'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'GMD');

INSERT INTO currency (code, name, symbol)
SELECT 'GHS', 'Cedi ghanéen', 'GH₵'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'GHS');

INSERT INTO currency (code, name, symbol)
SELECT 'GNF', 'Franc guinéen', 'FG'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'GNF');

INSERT INTO currency (code, name, symbol)
SELECT 'LSL', 'Loti lesothan', 'L'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'LSL');

INSERT INTO currency (code, name, symbol)
SELECT 'LRD', 'Dollar libérien', 'L$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'LRD');

INSERT INTO currency (code, name, symbol)
SELECT 'MWK', 'Kwacha malawite', 'MK'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'MWK');

INSERT INTO currency (code, name, symbol)
SELECT 'MUR', 'Roupie mauricienne', '₨'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'MUR');

INSERT INTO currency (code, name, symbol)
SELECT 'MRU', 'Ouguiya mauritanien', 'UM'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'MRU');

INSERT INTO currency (code, name, symbol)
SELECT 'SSP', 'Livre sud-soudanaise', '£'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'SSP');

INSERT INTO currency (code, name, symbol)
SELECT 'STN', 'Dobra santoméen', 'Db'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'STN');

INSERT INTO currency (code, name, symbol)
SELECT 'SCR', 'Roupie seychelloise', '₨'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'SCR');

INSERT INTO currency (code, name, symbol)
SELECT 'SLL', 'Leone sierra-léonais', 'Le'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'SLL');

INSERT INTO currency (code, name, symbol)
SELECT 'FJD', 'Dollar fidjien', 'FJ$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'FJD');

INSERT INTO currency (code, name, symbol)
SELECT 'SBD', 'Dollar des Îles Salomon', 'SI$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'SBD');

INSERT INTO currency (code, name, symbol)
SELECT 'AUD', 'Dollar australien', 'AU$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'AUD');

INSERT INTO currency (code, name, symbol)
SELECT 'PGK', 'Kina papouasien', 'K'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'PGK');

INSERT INTO currency (code, name, symbol)
SELECT 'WST', 'Tala samoan', 'WS$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'WST');

INSERT INTO currency (code, name, symbol)
SELECT 'TOP', 'Paʻanga tongien', 'T$'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'TOP');

INSERT INTO currency (code, name, symbol)
SELECT 'VUV', 'Vatu vanuatuan', 'Vt'
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE code = 'VUV');

-- =====================================================
-- INSERTION DES PAYS MANQUANTS
-- =====================================================

-- Amérique du Nord et Centrale
INSERT INTO country (code, name, continent) VALUES ('BS', 'Bahamas', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('BB', 'Barbade', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('BZ', 'Belize', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('DM', 'Dominique', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('SV', 'El Salvador', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('GD', 'Grenade', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('GT', 'Guatemala', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('HT', 'Haïti', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('HN', 'Honduras', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('JM', 'Jamaïque', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('KN', 'Saint-Kitts-et-Nevis', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('LC', 'Sainte-Lucie', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('VC', 'Saint-Vincent-et-les-Grenadines', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('DO', 'République dominicaine', 'Amérique du Nord');
INSERT INTO country (code, name, continent) VALUES ('TT', 'Trinité-et-Tobago', 'Amérique du Nord');

-- Amérique du Sud
INSERT INTO country (code, name, continent) VALUES ('GY', 'Guyana', 'Amérique du Sud');
INSERT INTO country (code, name, continent) VALUES ('SR', 'Suriname', 'Amérique du Sud');
INSERT INTO country (code, name, continent) VALUES ('UY', 'Uruguay', 'Amérique du Sud');

-- Europe
INSERT INTO country (code, name, continent) VALUES ('LI', 'Liechtenstein', 'Europe');
INSERT INTO country (code, name, continent) VALUES ('MK', 'Macédoine du Nord', 'Europe');
INSERT INTO country (code, name, continent) VALUES ('MT', 'Malte', 'Europe');
INSERT INTO country (code, name, continent) VALUES ('MD', 'Moldavie', 'Europe');
INSERT INTO country (code, name, continent) VALUES ('SM', 'Saint-Marin', 'Europe');
INSERT INTO country (code, name, continent) VALUES ('VA', 'Vatican', 'Europe');

-- Asie
INSERT INTO country (code, name, continent) VALUES ('BH', 'Bahreïn', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('BD', 'Bangladesh', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('BT', 'Bhoutan', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('BN', 'Brunei', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('GE', 'Géorgie', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('LA', 'Laos', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('MV', 'Maldives', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('MM', 'Myanmar', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('OM', 'Oman', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('LK', 'Sri Lanka', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('TJ', 'Tadjikistan', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('TL', 'Timor oriental', 'Asie');
INSERT INTO country (code, name, continent) VALUES ('TM', 'Turkménistan', 'Asie');

-- Afrique
INSERT INTO country (code, name, continent) VALUES ('CV', 'Cabo Verde', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('KM', 'Comores', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('CG', 'Congo', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('CD', 'RD Congo', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('CI', 'Côte d''Ivoire', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('DJ', 'Djibouti', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('ER', 'Érythrée', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('SZ', 'Eswatini', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('ET', 'Éthiopie', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('GA', 'Gabon', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('GM', 'Gambie', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('GH', 'Ghana', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('GN', 'Guinée', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('GW', 'Guinée-Bissau', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('GQ', 'Guinée équatoriale', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('LS', 'Lesotho', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('LR', 'Liberia', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('MW', 'Malawi', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('MU', 'Maurice', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('MR', 'Mauritanie', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('CF', 'République centrafricaine', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('ST', 'São Tomé-et-Príncipe', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('SC', 'Seychelles', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('SL', 'Sierra Leone', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('SS', 'Soudan du Sud', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('TD', 'Tchad', 'Afrique');
INSERT INTO country (code, name, continent) VALUES ('TG', 'Togo', 'Afrique');

-- Océanie
INSERT INTO country (code, name, continent) VALUES ('FJ', 'Fidji', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('MH', 'Îles Marshall', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('SB', 'Îles Salomon', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('KI', 'Kiribati', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('FM', 'Micronésie', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('NR', 'Nauru', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('PW', 'Palaos', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('PG', 'Papouasie-Nouvelle-Guinée', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('WS', 'Samoa', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('TO', 'Tonga', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('TV', 'Tuvalu', 'Océanie');
INSERT INTO country (code, name, continent) VALUES ('VU', 'Vanuatu', 'Océanie');

-- =====================================================
-- LIAISON PAYS - DEVISES
-- =====================================================

-- Amérique du Nord et Centrale
UPDATE country SET currency = 'BSD' WHERE code = 'BS';
UPDATE country SET currency = 'BBD' WHERE code = 'BB';
UPDATE country SET currency = 'BZD' WHERE code = 'BZ';
UPDATE country SET currency = 'XCD' WHERE code = 'DM';
UPDATE country SET currency = 'USD' WHERE code = 'SV';
UPDATE country SET currency = 'XCD' WHERE code = 'GD';
UPDATE country SET currency = 'GTQ' WHERE code = 'GT';
UPDATE country SET currency = 'HTG' WHERE code = 'HT';
UPDATE country SET currency = 'HNL' WHERE code = 'HN';
UPDATE country SET currency = 'JMD' WHERE code = 'JM';
UPDATE country SET currency = 'XCD' WHERE code = 'KN';
UPDATE country SET currency = 'XCD' WHERE code = 'LC';
UPDATE country SET currency = 'XCD' WHERE code = 'VC';
UPDATE country SET currency = 'DOP' WHERE code = 'DO';
UPDATE country SET currency = 'TTD' WHERE code = 'TT';

-- Amérique du Sud
UPDATE country SET currency = 'GYD' WHERE code = 'GY';
UPDATE country SET currency = 'SRD' WHERE code = 'SR';
UPDATE country SET currency = 'UYU' WHERE code = 'UY';

-- Europe
UPDATE country SET currency = 'CHF' WHERE code = 'LI';
UPDATE country SET currency = 'MKD' WHERE code = 'MK';
UPDATE country SET currency = 'EUR' WHERE code = 'MT';
UPDATE country SET currency = 'MDL' WHERE code = 'MD';
UPDATE country SET currency = 'EUR' WHERE code = 'SM';
UPDATE country SET currency = 'EUR' WHERE code = 'VA';

-- Asie
UPDATE country SET currency = 'BHD' WHERE code = 'BH';
UPDATE country SET currency = 'BDT' WHERE code = 'BD';
UPDATE country SET currency = 'BTN' WHERE code = 'BT';
UPDATE country SET currency = 'BND' WHERE code = 'BN';
UPDATE country SET currency = 'GEL' WHERE code = 'GE';
UPDATE country SET currency = 'LAK' WHERE code = 'LA';
UPDATE country SET currency = 'MVR' WHERE code = 'MV';
UPDATE country SET currency = 'MMK' WHERE code = 'MM';
UPDATE country SET currency = 'OMR' WHERE code = 'OM';
UPDATE country SET currency = 'LKR' WHERE code = 'LK';
UPDATE country SET currency = 'TJS' WHERE code = 'TJ';
UPDATE country SET currency = 'USD' WHERE code = 'TL';
UPDATE country SET currency = 'TMT' WHERE code = 'TM';

-- Afrique
UPDATE country SET currency = 'CVE' WHERE code = 'CV';
UPDATE country SET currency = 'KMF' WHERE code = 'KM';
UPDATE country SET currency = 'XAF' WHERE code = 'CG';
UPDATE country SET currency = 'CDF' WHERE code = 'CD';
UPDATE country SET currency = 'XOF' WHERE code = 'CI';
UPDATE country SET currency = 'DJF' WHERE code = 'DJ';
UPDATE country SET currency = 'ERN' WHERE code = 'ER';
UPDATE country SET currency = 'SZL' WHERE code = 'SZ';
UPDATE country SET currency = 'ETB' WHERE code = 'ET';
UPDATE country SET currency = 'XAF' WHERE code = 'GA';
UPDATE country SET currency = 'GMD' WHERE code = 'GM';
UPDATE country SET currency = 'GHS' WHERE code = 'GH';
UPDATE country SET currency = 'GNF' WHERE code = 'GN';
UPDATE country SET currency = 'XOF' WHERE code = 'GW';
UPDATE country SET currency = 'XAF' WHERE code = 'GQ';
UPDATE country SET currency = 'LSL' WHERE code = 'LS';
UPDATE country SET currency = 'LRD' WHERE code = 'LR';
UPDATE country SET currency = 'MWK' WHERE code = 'MW';
UPDATE country SET currency = 'MUR' WHERE code = 'MU';
UPDATE country SET currency = 'MRU' WHERE code = 'MR';
UPDATE country SET currency = 'XAF' WHERE code = 'CF';
UPDATE country SET currency = 'STN' WHERE code = 'ST';
UPDATE country SET currency = 'SCR' WHERE code = 'SC';
UPDATE country SET currency = 'SLL' WHERE code = 'SL';
UPDATE country SET currency = 'SSP' WHERE code = 'SS';
UPDATE country SET currency = 'XAF' WHERE code = 'TD';
UPDATE country SET currency = 'XOF' WHERE code = 'TG';

-- Océanie
UPDATE country SET currency = 'FJD' WHERE code = 'FJ';
UPDATE country SET currency = 'USD' WHERE code = 'MH';
UPDATE country SET currency = 'SBD' WHERE code = 'SB';
UPDATE country SET currency = 'AUD' WHERE code = 'KI';
UPDATE country SET currency = 'USD' WHERE code = 'FM';
UPDATE country SET currency = 'AUD' WHERE code = 'NR';
UPDATE country SET currency = 'USD' WHERE code = 'PW';
UPDATE country SET currency = 'PGK' WHERE code = 'PG';
UPDATE country SET currency = 'WST' WHERE code = 'WS';
UPDATE country SET currency = 'TOP' WHERE code = 'TO';
UPDATE country SET currency = 'AUD' WHERE code = 'TV';
UPDATE country SET currency = 'VUV' WHERE code = 'VU';
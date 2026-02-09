<?php

namespace App\Command;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Repository\CountryRepository;
use App\Entity\ClimateData;

class ImportClimateDataCommand extends Command
{
    protected static $defaultName = 'app:import-climate-data';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ManagerRegistry     $managerRegistry,
        private readonly CountryRepository   $countryRepository,
        private readonly string              $weatherApiKey,
        private readonly LoggerInterface     $climateLogger
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Importe les données climatiques moyennes sur 10 ans')
            ->addArgument('city', InputArgument::OPTIONAL, 'Ville spécifique à importer')
            ->addArgument('country', InputArgument::OPTIONAL, 'Pays de la ville');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import des données climatiques sur 10 ans');

        $cityFilter = $input->getArgument('city');
        if ($cityFilter) {
            $this->importCityData($cityFilter);
            $io->success("Import terminé pour {$cityFilter}");
            return Command::SUCCESS;
        }

        $citiesToImport = $this->getCitiesList();
        $io->progressStart(count($citiesToImport));

        foreach ($citiesToImport as $cityData) {
            $maxAttempts = 2;
            $attempt = 0;
            $success = false;

            while ($attempt < $maxAttempts && !$success) {
                try {
                    $attempt++;

                    if ($attempt > 1) {
                        $io->note("Tentative {$attempt}/{$maxAttempts} pour {$cityData['name']}...");
                        sleep(2);
                    }

                    $this->importCityData($cityData['name'], $cityData['country'] ?? null);
                    $success = true;
                    $io->progressAdvance();

                } catch (\Exception $e) {
                    if ($attempt >= $maxAttempts) {
                        // Échec définitif après toutes les tentatives
                        $this->climateLogger->error("Échec définitif pour {$cityData['name']}: {$e->getMessage()}");
                        $io->progressAdvance();
                    } else {
                        $this->climateLogger->warning("Erreur pour {$cityData['name']} (tentative {$attempt}/{$maxAttempts}): {$e->getMessage()}");
                    }
                }
            }

            if ($success) {
                usleep(600000); // 0.6 secondes entre chaque ville réussie
            }
        }

        $io->progressFinish();
        $io->success('Import terminé !');

        return Command::SUCCESS;
    }

    private function getCitiesList(): array
    {
        $cities = [];

        // 1. Toutes les capitales
        $countries = $this->countryRepository->findAll();
        foreach ($countries as $country) {
            if ($capital = $country->getCapital()) {
                $cities[] = [
                    'name' => $capital,
                    'country' => $country->getName()
                ];
            }
        }

        // 2. Grandes villes touristiques
        $majorCities = [
            // 🇫🇷 FRANCE
            ['name' => 'Nice', 'country' => 'France'],
            ['name' => 'Lyon', 'country' => 'France'],
            ['name' => 'Marseille', 'country' => 'France'],
            ['name' => 'Bordeaux', 'country' => 'France'],
            ['name' => 'Strasbourg', 'country' => 'France'],
            ['name' => 'Toulouse', 'country' => 'France'],
            ['name' => 'Nantes', 'country' => 'France'],
            ['name' => 'Montpellier', 'country' => 'France'],
            ['name' => 'Lille', 'country' => 'France'],
            ['name' => 'Rennes', 'country' => 'France'],
            ['name' => 'Reims', 'country' => 'France'],
            ['name' => 'Le Havre', 'country' => 'France'],
            ['name' => 'Saint-Étienne', 'country' => 'France'],
            ['name' => 'Toulon', 'country' => 'France'],
            ['name' => 'Grenoble', 'country' => 'France'],
            ['name' => 'Dijon', 'country' => 'France'],
            ['name' => 'Angers', 'country' => 'France'],
            ['name' => 'Cannes', 'country' => 'France'],
            ['name' => 'Avignon', 'country' => 'France'],
            ['name' => 'Aix-en-Provence', 'country' => 'France'],
            ['name' => 'Biarritz', 'country' => 'France'],
            ['name' => 'La Rochelle', 'country' => 'France'],
            ['name' => 'Annecy', 'country' => 'France'],
            ['name' => 'Chamonix', 'country' => 'France'],
            ['name' => 'Tours', 'country' => 'France'],

            // 🇺🇸 USA
            ['name' => 'New York', 'country' => 'USA'],
            ['name' => 'Los Angeles', 'country' => 'USA'],
            ['name' => 'Miami', 'country' => 'USA'],
            ['name' => 'San Francisco', 'country' => 'USA'],
            ['name' => 'Las Vegas', 'country' => 'USA'],
            ['name' => 'Chicago', 'country' => 'USA'],
            ['name' => 'Orlando', 'country' => 'USA'],
            ['name' => 'Seattle', 'country' => 'USA'],
            ['name' => 'Boston', 'country' => 'USA'],
            ['name' => 'San Diego', 'country' => 'USA'],
            ['name' => 'Austin', 'country' => 'USA'],
            ['name' => 'Houston', 'country' => 'USA'],
            ['name' => 'Dallas', 'country' => 'USA'],
            ['name' => 'Phoenix', 'country' => 'USA'],
            ['name' => 'Philadelphia', 'country' => 'USA'],
            ['name' => 'Nashville', 'country' => 'USA'],
            ['name' => 'New Orleans', 'country' => 'USA'],
            ['name' => 'Denver', 'country' => 'USA'],
            ['name' => 'Portland', 'country' => 'USA'],
            ['name' => 'Atlanta', 'country' => 'USA'],
            ['name' => 'Tampa', 'country' => 'USA'],
            ['name' => 'Honolulu', 'country' => 'USA'],
            ['name' => 'Anchorage', 'country' => 'USA'],
            ['name' => 'Charleston', 'country' => 'USA'],
            ['name' => 'Savannah', 'country' => 'USA'],
            ['name' => 'Key West', 'country' => 'USA'],

            // 🇯🇵 JAPON
            ['name' => 'Osaka', 'country' => 'Japan'],
            ['name' => 'Kyoto', 'country' => 'Japan'],
            ['name' => 'Hiroshima', 'country' => 'Japan'],
            ['name' => 'Nara', 'country' => 'Japan'],
            ['name' => 'Sapporo', 'country' => 'Japan'],
            ['name' => 'Yokohama', 'country' => 'Japan'],
            ['name' => 'Nagoya', 'country' => 'Japan'],
            ['name' => 'Fukuoka', 'country' => 'Japan'],
            ['name' => 'Kobe', 'country' => 'Japan'],
            ['name' => 'Kawasaki', 'country' => 'Japan'],
            ['name' => 'Sendai', 'country' => 'Japan'],
            ['name' => 'Nagasaki', 'country' => 'Japan'],
            ['name' => 'Kamakura', 'country' => 'Japan'],
            ['name' => 'Hakone', 'country' => 'Japan'],
            ['name' => 'Nikko', 'country' => 'Japan'],

            // 🇮🇹 ITALIE
            ['name' => 'Venice', 'country' => 'Italy'],
            ['name' => 'Florence', 'country' => 'Italy'],
            ['name' => 'Milan', 'country' => 'Italy'],
            ['name' => 'Naples', 'country' => 'Italy'],
            ['name' => 'Bologna', 'country' => 'Italy'],
            ['name' => 'Verona', 'country' => 'Italy'],
            ['name' => 'Turin', 'country' => 'Italy'],
            ['name' => 'Genoa', 'country' => 'Italy'],
            ['name' => 'Palermo', 'country' => 'Italy'],
            ['name' => 'Pisa', 'country' => 'Italy'],
            ['name' => 'Siena', 'country' => 'Italy'],
            ['name' => 'Amalfi', 'country' => 'Italy'],
            ['name' => 'Sorrento', 'country' => 'Italy'],
            ['name' => 'Capri', 'country' => 'Italy'],
            ['name' => 'Catania', 'country' => 'Italy'],
            ['name' => 'Rimini', 'country' => 'Italy'],

            // 🇪🇸 ESPAGNE
            ['name' => 'Barcelona', 'country' => 'Spain'],
            ['name' => 'Seville', 'country' => 'Spain'],
            ['name' => 'Valencia', 'country' => 'Spain'],
            ['name' => 'Granada', 'country' => 'Spain'],
            ['name' => 'Bilbao', 'country' => 'Spain'],
            ['name' => 'Malaga', 'country' => 'Spain'],
            ['name' => 'Cordoba', 'country' => 'Spain'],
            ['name' => 'Toledo', 'country' => 'Spain'],
            ['name' => 'Salamanca', 'country' => 'Spain'],
            ['name' => 'San Sebastian', 'country' => 'Spain'],
            ['name' => 'Ibiza', 'country' => 'Spain'],
            ['name' => 'Palma de Mallorca', 'country' => 'Spain'],
            ['name' => 'Marbella', 'country' => 'Spain'],
            ['name' => 'Cadiz', 'country' => 'Spain'],
            ['name' => 'Zaragoza', 'country' => 'Spain'],

            // 🇬🇧 ROYAUME-UNI
            ['name' => 'Edinburgh', 'country' => 'United Kingdom'],
            ['name' => 'Manchester', 'country' => 'United Kingdom'],
            ['name' => 'Liverpool', 'country' => 'United Kingdom'],
            ['name' => 'Oxford', 'country' => 'United Kingdom'],
            ['name' => 'Cambridge', 'country' => 'United Kingdom'],
            ['name' => 'Glasgow', 'country' => 'United Kingdom'],
            ['name' => 'Birmingham', 'country' => 'United Kingdom'],
            ['name' => 'Bristol', 'country' => 'United Kingdom'],
            ['name' => 'Brighton', 'country' => 'United Kingdom'],
            ['name' => 'Bath', 'country' => 'United Kingdom'],
            ['name' => 'York', 'country' => 'United Kingdom'],
            ['name' => 'Newcastle', 'country' => 'United Kingdom'],
            ['name' => 'Cardiff', 'country' => 'United Kingdom'],
            ['name' => 'Belfast', 'country' => 'United Kingdom'],
            ['name' => 'Inverness', 'country' => 'United Kingdom'],

            // 🇩🇪 ALLEMAGNE
            ['name' => 'Munich', 'country' => 'Germany'],
            ['name' => 'Hamburg', 'country' => 'Germany'],
            ['name' => 'Frankfurt', 'country' => 'Germany'],
            ['name' => 'Cologne', 'country' => 'Germany'],
            ['name' => 'Dresden', 'country' => 'Germany'],
            ['name' => 'Stuttgart', 'country' => 'Germany'],
            ['name' => 'Dusseldorf', 'country' => 'Germany'],
            ['name' => 'Dortmund', 'country' => 'Germany'],
            ['name' => 'Leipzig', 'country' => 'Germany'],
            ['name' => 'Bremen', 'country' => 'Germany'],
            ['name' => 'Hanover', 'country' => 'Germany'],
            ['name' => 'Nuremberg', 'country' => 'Germany'],
            ['name' => 'Heidelberg', 'country' => 'Germany'],
            ['name' => 'Freiburg', 'country' => 'Germany'],

            // 🇹🇭 THAÏLANDE
            ['name' => 'Phuket', 'country' => 'Thailand'],
            ['name' => 'Chiang Mai', 'country' => 'Thailand'],
            ['name' => 'Pattaya', 'country' => 'Thailand'],
            ['name' => 'Krabi', 'country' => 'Thailand'],
            ['name' => 'Koh Samui', 'country' => 'Thailand'],
            ['name' => 'Hua Hin', 'country' => 'Thailand'],
            ['name' => 'Ayutthaya', 'country' => 'Thailand'],

            // 🇦🇪 ÉMIRATS ARABES UNIS
            ['name' => 'Dubai', 'country' => 'UAE'],
            ['name' => 'Abu Dhabi', 'country' => 'UAE'],
            ['name' => 'Sharjah', 'country' => 'UAE'],

            // 🇨🇳 CHINE
            ['name' => 'Hong Kong', 'country' => 'China'],
            ['name' => 'Shanghai', 'country' => 'China'],
            ['name' => 'Beijing', 'country' => 'China'],
            ['name' => 'Guangzhou', 'country' => 'China'],
            ['name' => 'Shenzhen', 'country' => 'China'],
            ['name' => 'Chengdu', 'country' => 'China'],
            ['name' => 'Xian', 'country' => 'China'],
            ['name' => 'Hangzhou', 'country' => 'China'],
            ['name' => 'Suzhou', 'country' => 'China'],

            // 🇦🇺 AUSTRALIE
            ['name' => 'Sydney', 'country' => 'Australia'],
            ['name' => 'Melbourne', 'country' => 'Australia'],
            ['name' => 'Brisbane', 'country' => 'Australia'],
            ['name' => 'Perth', 'country' => 'Australia'],
            ['name' => 'Adelaide', 'country' => 'Australia'],
            ['name' => 'Gold Coast', 'country' => 'Australia'],
            ['name' => 'Cairns', 'country' => 'Australia'],

            // 🇹🇷 TURQUIE
            ['name' => 'Istanbul', 'country' => 'Turkey'],
            ['name' => 'Ankara', 'country' => 'Turkey'],
            ['name' => 'Izmir', 'country' => 'Turkey'],
            ['name' => 'Antalya', 'country' => 'Turkey'],
            ['name' => 'Bodrum', 'country' => 'Turkey'],
            ['name' => 'Cappadocia', 'country' => 'Turkey'],

            // 🇲🇦 MAROC
            ['name' => 'Marrakech', 'country' => 'Morocco'],
            ['name' => 'Casablanca', 'country' => 'Morocco'],
            ['name' => 'Fez', 'country' => 'Morocco'],
            ['name' => 'Tangier', 'country' => 'Morocco'],
            ['name' => 'Rabat', 'country' => 'Morocco'],
            ['name' => 'Agadir', 'country' => 'Morocco'],

            // 🇲🇽 MEXIQUE
            ['name' => 'Cancun', 'country' => 'Mexico'],
            ['name' => 'Mexico City', 'country' => 'Mexico'],
            ['name' => 'Playa del Carmen', 'country' => 'Mexico'],
            ['name' => 'Tulum', 'country' => 'Mexico'],
            ['name' => 'Guadalajara', 'country' => 'Mexico'],
            ['name' => 'Puerto Vallarta', 'country' => 'Mexico'],
            ['name' => 'Los Cabos', 'country' => 'Mexico'],

            // 🇧🇷 BRÉSIL
            ['name' => 'Rio de Janeiro', 'country' => 'Brazil'],
            ['name' => 'Sao Paulo', 'country' => 'Brazil'],
            ['name' => 'Salvador', 'country' => 'Brazil'],
            ['name' => 'Brasilia', 'country' => 'Brazil'],
            ['name' => 'Florianopolis', 'country' => 'Brazil'],
            ['name' => 'Fortaleza', 'country' => 'Brazil'],

            // 🇦🇷 ARGENTINE
            ['name' => 'Buenos Aires', 'country' => 'Argentina'],
            ['name' => 'Mendoza', 'country' => 'Argentina'],
            ['name' => 'Cordoba', 'country' => 'Argentina'],
            ['name' => 'Bariloche', 'country' => 'Argentina'],

            // 🇿🇦 AFRIQUE DU SUD
            ['name' => 'Cape Town', 'country' => 'South Africa'],
            ['name' => 'Johannesburg', 'country' => 'South Africa'],
            ['name' => 'Durban', 'country' => 'South Africa'],
            ['name' => 'Pretoria', 'country' => 'South Africa'],

            // 🇸🇬 SINGAPOUR
            ['name' => 'Singapore', 'country' => 'Singapore'],

            // 🇰🇷 CORÉE DU SUD
            ['name' => 'Seoul', 'country' => 'South Korea'],
            ['name' => 'Busan', 'country' => 'South Korea'],
            ['name' => 'Jeju', 'country' => 'South Korea'],
            ['name' => 'Incheon', 'country' => 'South Korea'],

            // 🇻🇳 VIETNAM
            ['name' => 'Hanoi', 'country' => 'Vietnam'],
            ['name' => 'Ho Chi Minh City', 'country' => 'Vietnam'],
            ['name' => 'Da Nang', 'country' => 'Vietnam'],
            ['name' => 'Hoi An', 'country' => 'Vietnam'],
            ['name' => 'Nha Trang', 'country' => 'Vietnam'],
            ['name' => 'Hue', 'country' => 'Vietnam'],

            // 🇮🇩 INDONÉSIE
            ['name' => 'Jakarta', 'country' => 'Indonesia'],
            ['name' => 'Bali', 'country' => 'Indonesia'],
            ['name' => 'Yogyakarta', 'country' => 'Indonesia'],
            ['name' => 'Bandung', 'country' => 'Indonesia'],
            ['name' => 'Surabaya', 'country' => 'Indonesia'],

            // 🇲🇾 MALAISIE
            ['name' => 'Kuala Lumpur', 'country' => 'Malaysia'],
            ['name' => 'Penang', 'country' => 'Malaysia'],
            ['name' => 'Langkawi', 'country' => 'Malaysia'],
            ['name' => 'Malacca', 'country' => 'Malaysia'],

            // 🇵🇭 PHILIPPINES
            ['name' => 'Manila', 'country' => 'Philippines'],
            ['name' => 'Cebu', 'country' => 'Philippines'],
            ['name' => 'Boracay', 'country' => 'Philippines'],
            ['name' => 'Palawan', 'country' => 'Philippines'],

            // 🇮🇳 INDE
            ['name' => 'Delhi', 'country' => 'India'],
            ['name' => 'Mumbai', 'country' => 'India'],
            ['name' => 'Bangalore', 'country' => 'India'],
            ['name' => 'Jaipur', 'country' => 'India'],
            ['name' => 'Agra', 'country' => 'India'],
            ['name' => 'Goa', 'country' => 'India'],
            ['name' => 'Kerala', 'country' => 'India'],
            ['name' => 'Varanasi', 'country' => 'India'],

            // 🇳🇵 NÉPAL
            ['name' => 'Kathmandu', 'country' => 'Nepal'],
            ['name' => 'Pokhara', 'country' => 'Nepal'],

            // 🇱🇰 SRI LANKA
            ['name' => 'Colombo', 'country' => 'Sri Lanka'],
            ['name' => 'Kandy', 'country' => 'Sri Lanka'],
            ['name' => 'Galle', 'country' => 'Sri Lanka'],

            // 🇪🇬 ÉGYPTE
            ['name' => 'Cairo', 'country' => 'Egypt'],
            ['name' => 'Alexandria', 'country' => 'Egypt'],
            ['name' => 'Luxor', 'country' => 'Egypt'],
            ['name' => 'Aswan', 'country' => 'Egypt'],
            ['name' => 'Sharm el-Sheikh', 'country' => 'Egypt'],

            // 🇯🇴 JORDANIE
            ['name' => 'Amman', 'country' => 'Jordan'],
            ['name' => 'Petra', 'country' => 'Jordan'],
            ['name' => 'Aqaba', 'country' => 'Jordan'],

            // 🇮🇱 ISRAËL
            ['name' => 'Jerusalem', 'country' => 'Israel'],
            ['name' => 'Tel Aviv', 'country' => 'Israel'],
            ['name' => 'Haifa', 'country' => 'Israel'],
            ['name' => 'Eilat', 'country' => 'Israel'],

            // 🇬🇷 GRÈCE
            ['name' => 'Athens', 'country' => 'Greece'],
            ['name' => 'Thessaloniki', 'country' => 'Greece'],
            ['name' => 'Santorini', 'country' => 'Greece'],
            ['name' => 'Mykonos', 'country' => 'Greece'],
            ['name' => 'Crete', 'country' => 'Greece'],
            ['name' => 'Rhodes', 'country' => 'Greece'],

            // 🇭🇷 CROATIE
            ['name' => 'Zagreb', 'country' => 'Croatia'],
            ['name' => 'Dubrovnik', 'country' => 'Croatia'],
            ['name' => 'Split', 'country' => 'Croatia'],
            ['name' => 'Pula', 'country' => 'Croatia'],

            // 🇵🇹 PORTUGAL
            ['name' => 'Lisbon', 'country' => 'Portugal'],
            ['name' => 'Porto', 'country' => 'Portugal'],
            ['name' => 'Faro', 'country' => 'Portugal'],
            ['name' => 'Lagos', 'country' => 'Portugal'],
            ['name' => 'Madeira', 'country' => 'Portugal'],
            ['name' => 'Azores', 'country' => 'Portugal'],

            // 🇦🇹 AUTRICHE
            ['name' => 'Vienna', 'country' => 'Austria'],
            ['name' => 'Salzburg', 'country' => 'Austria'],
            ['name' => 'Innsbruck', 'country' => 'Austria'],
            ['name' => 'Graz', 'country' => 'Austria'],

            // 🇨🇭 SUISSE
            ['name' => 'Zurich', 'country' => 'Switzerland'],
            ['name' => 'Geneva', 'country' => 'Switzerland'],
            ['name' => 'Bern', 'country' => 'Switzerland'],
            ['name' => 'Lucerne', 'country' => 'Switzerland'],
            ['name' => 'Interlaken', 'country' => 'Switzerland'],
            ['name' => 'Zermatt', 'country' => 'Switzerland'],

            // 🇧🇪 BELGIQUE
            ['name' => 'Brussels', 'country' => 'Belgium'],
            ['name' => 'Bruges', 'country' => 'Belgium'],
            ['name' => 'Ghent', 'country' => 'Belgium'],
            ['name' => 'Antwerp', 'country' => 'Belgium'],

            // 🇳🇱 PAYS-BAS
            ['name' => 'Amsterdam', 'country' => 'Netherlands'],
            ['name' => 'Rotterdam', 'country' => 'Netherlands'],
            ['name' => 'The Hague', 'country' => 'Netherlands'],
            ['name' => 'Utrecht', 'country' => 'Netherlands'],
            ['name' => 'Eindhoven', 'country' => 'Netherlands'],

            // 🇩🇰 DANEMARK
            ['name' => 'Copenhagen', 'country' => 'Denmark'],
            ['name' => 'Aarhus', 'country' => 'Denmark'],
            ['name' => 'Odense', 'country' => 'Denmark'],

            // 🇸🇪 SUÈDE
            ['name' => 'Stockholm', 'country' => 'Sweden'],
            ['name' => 'Gothenburg', 'country' => 'Sweden'],
            ['name' => 'Malmo', 'country' => 'Sweden'],

            // 🇳🇴 NORVÈGE
            ['name' => 'Oslo', 'country' => 'Norway'],
            ['name' => 'Bergen', 'country' => 'Norway'],
            ['name' => 'Trondheim', 'country' => 'Norway'],
            ['name' => 'Tromso', 'country' => 'Norway'],

            // 🇫🇮 FINLANDE
            ['name' => 'Helsinki', 'country' => 'Finland'],
            ['name' => 'Rovaniemi', 'country' => 'Finland'],
            ['name' => 'Tampere', 'country' => 'Finland'],

            // 🇮🇸 ISLANDE
            ['name' => 'Reykjavik', 'country' => 'Iceland'],
            ['name' => 'Akureyri', 'country' => 'Iceland'],

            // 🇮🇪 IRLANDE
            ['name' => 'Dublin', 'country' => 'Ireland'],
            ['name' => 'Cork', 'country' => 'Ireland'],
            ['name' => 'Galway', 'country' => 'Ireland'],
            ['name' => 'Killarney', 'country' => 'Ireland'],

            // 🇵🇱 POLOGNE
            ['name' => 'Warsaw', 'country' => 'Poland'],
            ['name' => 'Krakow', 'country' => 'Poland'],
            ['name' => 'Gdansk', 'country' => 'Poland'],
            ['name' => 'Wroclaw', 'country' => 'Poland'],

            // 🇨🇿 RÉPUBLIQUE TCHÈQUE
            ['name' => 'Prague', 'country' => 'Czech Republic'],
            ['name' => 'Brno', 'country' => 'Czech Republic'],
            ['name' => 'Cesky Krumlov', 'country' => 'Czech Republic'],

            // 🇭🇺 HONGRIE
            ['name' => 'Budapest', 'country' => 'Hungary'],

            // 🇷🇴 ROUMANIE
            ['name' => 'Bucharest', 'country' => 'Romania'],
            ['name' => 'Brasov', 'country' => 'Romania'],
            ['name' => 'Cluj-Napoca', 'country' => 'Romania'],

            // 🇧🇬 BULGARIE
            ['name' => 'Sofia', 'country' => 'Bulgaria'],
            ['name' => 'Plovdiv', 'country' => 'Bulgaria'],
            ['name' => 'Varna', 'country' => 'Bulgaria'],

            // 🇷🇺 RUSSIE
            ['name' => 'Moscow', 'country' => 'Russia'],
            ['name' => 'Saint Petersburg', 'country' => 'Russia'],
            ['name' => 'Kazan', 'country' => 'Russia'],
            ['name' => 'Sochi', 'country' => 'Russia'],

            // 🇺🇦 UKRAINE
            ['name' => 'Kyiv', 'country' => 'Ukraine'],
            ['name' => 'Lviv', 'country' => 'Ukraine'],
            ['name' => 'Odessa', 'country' => 'Ukraine'],

            // 🇨🇦 CANADA
            ['name' => 'Toronto', 'country' => 'Canada'],
            ['name' => 'Montreal', 'country' => 'Canada'],
            ['name' => 'Vancouver', 'country' => 'Canada'],
            ['name' => 'Quebec City', 'country' => 'Canada'],
            ['name' => 'Calgary', 'country' => 'Canada'],
            ['name' => 'Ottawa', 'country' => 'Canada'],
            ['name' => 'Banff', 'country' => 'Canada'],

            // 🇨🇱 CHILI
            ['name' => 'Santiago', 'country' => 'Chile'],
            ['name' => 'Valparaiso', 'country' => 'Chile'],
            ['name' => 'Patagonia', 'country' => 'Chile'],

            // 🇵🇪 PÉROU
            ['name' => 'Lima', 'country' => 'Peru'],
            ['name' => 'Cusco', 'country' => 'Peru'],
            ['name' => 'Arequipa', 'country' => 'Peru'],

            // 🇨🇴 COLOMBIE
            ['name' => 'Bogota', 'country' => 'Colombia'],
            ['name' => 'Medellin', 'country' => 'Colombia'],
            ['name' => 'Cartagena', 'country' => 'Colombia'],

            // 🇨🇷 COSTA RICA
            ['name' => 'San Jose', 'country' => 'Costa Rica'],
            ['name' => 'Tamarindo', 'country' => 'Costa Rica'],
            ['name' => 'Monteverde', 'country' => 'Costa Rica'],

            // 🇨🇺 CUBA
            ['name' => 'Havana', 'country' => 'Cuba'],
            ['name' => 'Varadero', 'country' => 'Cuba'],
            ['name' => 'Trinidad', 'country' => 'Cuba'],

            // 🇯🇲 JAMAÏQUE
            ['name' => 'Kingston', 'country' => 'Jamaica'],
            ['name' => 'Montego Bay', 'country' => 'Jamaica'],
            ['name' => 'Negril', 'country' => 'Jamaica'],

            // 🇩🇴 RÉPUBLIQUE DOMINICAINE
            ['name' => 'Santo Domingo', 'country' => 'Dominican Republic'],
            ['name' => 'Punta Cana', 'country' => 'Dominican Republic'],

            // 🇳🇿 NOUVELLE-ZÉLANDE
            ['name' => 'Auckland', 'country' => 'New Zealand'],
            ['name' => 'Wellington', 'country' => 'New Zealand'],
            ['name' => 'Queenstown', 'country' => 'New Zealand'],
            ['name' => 'Christchurch', 'country' => 'New Zealand'],

            // 🇫🇯 FIDJI
            ['name' => 'Suva', 'country' => 'Fiji'],
            ['name' => 'Nadi', 'country' => 'Fiji'],

            // 🇹🇳 TUNISIE
            ['name' => 'Tunis', 'country' => 'Tunisia'],
            ['name' => 'Djerba', 'country' => 'Tunisia'],
            ['name' => 'Hammamet', 'country' => 'Tunisia'],

            // 🇰🇪 KENYA
            ['name' => 'Nairobi', 'country' => 'Kenya'],
            ['name' => 'Mombasa', 'country' => 'Kenya'],

            // 🇹🇿 TANZANIE
            ['name' => 'Dar es Salaam', 'country' => 'Tanzania'],
            ['name' => 'Zanzibar', 'country' => 'Tanzania'],

            // 🇲🇺 MAURICE
            ['name' => 'Port Louis', 'country' => 'Mauritius'],

            // 🇸🇨 SEYCHELLES
            ['name' => 'Victoria', 'country' => 'Seychelles'],
            ['name' => 'Mahe', 'country' => 'Seychelles'],

            // 🇲🇻 MALDIVES
            ['name' => 'Male', 'country' => 'Maldives'],
        ];

        return array_merge($cities, $majorCities);
    }

    /**
     * @throws \Exception
     */
    private function importCityData(string $city, ?string $country = null): void
    {
        for ($month = 1; $month <= 12; $month++) {
            $monthlyData = $this->getMonthlyAverageOver10Years($city, $month);

            $climateData = $this->managerRegistry->getRepository(ClimateData::class)
                ->findByCityAndMonth($city, $month, $country);

            if (!$climateData) {
                $climateData = new ClimateData();
                $climateData->setCity($city);
                $climateData->setMonth($month);
                $climateData->setCountry($country);
            }

            $climateData->setTempMinAvg($monthlyData['temp_min']);
            $climateData->setTempMaxAvg($monthlyData['temp_max']);
            $climateData->setPrecipitationMm($monthlyData['precipitation']);
            $climateData->setRainyDays($monthlyData['rainy_days']);
            $climateData->setSunshineHours($monthlyData['daylight_hours']);
            $climateData->setHumidityAvg($monthlyData['humidity']);
            $climateData->setSource('WeatherAPI - Moyenne 10 ans');
            $climateData->setLastUpdated(new \DateTime());

            $this->managerRegistry->getManager()->persist($climateData);
            $this->managerRegistry->getManager()->flush();
        }
    }

    private function getMonthlyAverageOver10Years(string $city, int $month): array
    {
        $allTempsMin = [];
        $allTempsMax = [];
        $allPrecipitations = [];
        $allHumidities = [];
        $allDaylightHours = [];
        $rainyDaysCount = 0;
        $totalDays = 0;

        $currentYear = (int)date('Y');

        for ($yearsAgo = 1; $yearsAgo <= 10; $yearsAgo++) {
            $year = $currentYear - $yearsAgo;
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

            try {
                $response = $this->httpClient->request('GET', 'https://api.weatherapi.com/v1/history.json', [
                    'query' => [
                        'key' => $this->weatherApiKey,
                        'q' => $city,
                        'dt' => $startDate,
                        'end_dt' => $endDate,
                        'lang' => 'fr',
                    ],
                    'timeout' => 30,
                ]);

                $data = $response->toArray();

                if (!isset($data['forecast']['forecastday'])) {
                    $this->climateLogger->error("Pas de données pour {$city} {$year}-{$month}");
                    continue;
                }

                foreach ($data['forecast']['forecastday'] as $day) {
                    $dayData = $day['day'];

                    $allTempsMin[] = $dayData['mintemp_c'];
                    $allTempsMax[] = $dayData['maxtemp_c'];
                    $allPrecipitations[] = $dayData['totalprecip_mm'] ?? 0;
                    $allHumidities[] = $dayData['avghumidity'];

                    // Calculer les heures de jour depuis les données astro
                    if (isset($day['astro']['sunrise']) && isset($day['astro']['sunset'])) {
                        $daylight = $this->calculateDaylightFromAstro(
                            $day['astro']['sunrise'],
                            $day['astro']['sunset']
                        );
                        if ($daylight > 0) {
                            $allDaylightHours[] = $daylight;
                        }
                    }

                    if (($dayData['totalprecip_mm'] ?? 0) > 2) {
                        $rainyDaysCount++;
                    }

                    $totalDays++;
                }

                usleep(300000);
            } catch (\Exception $e) {
                $this->climateLogger->error("Erreur pour {$city} {$year}-{$month}: {$e->getMessage()}");
            }
        }

        if (empty($allTempsMin)) {
            $this->climateLogger->error("Aucune donnée récupérée pour {$city} mois {$month}");
            throw new \Exception("Aucune donnée récupérée pour {$city} mois {$month}");
        }

        // Calculer les moyennes
        $tempMin = round(array_sum($allTempsMin) / count($allTempsMin), 1);
        $tempMax = round(array_sum($allTempsMax) / count($allTempsMax), 1);
        $avgPrecipPerDay = array_sum($allPrecipitations) / count($allPrecipitations);
        $totalPrecipMonth = round($avgPrecipPerDay * 30, 1);
        $rainyDaysPerMonth = round(($rainyDaysCount / $totalDays) * 30);
        $avgHumidity = round(array_sum($allHumidities) / count($allHumidities));

        // Moyenne des heures de jour (ou fallback si pas de données astro)
        $avgDaylightHours = !empty($allDaylightHours)
            ? round(array_sum($allDaylightHours) / count($allDaylightHours), 1)
            : $this->getFallbackDaylightHours($month);

        // Coefficient correctif humidité pour les mois de mousson
        if (in_array($month, [6, 7, 8, 9]) && $avgHumidity < 70) {
            $avgHumidity = min(95, $avgHumidity + 15);
        }

        return [
            'temp_min' => $tempMin,
            'temp_max' => $tempMax,
            'precipitation' => $totalPrecipMonth,
            'rainy_days' => $rainyDaysPerMonth,
            'daylight_hours' => $avgDaylightHours,
            'humidity' => $avgHumidity,
        ];
    }

    // Nouvelle fonction : Calculer les heures de jour depuis sunrise/sunset
    private function calculateDaylightFromAstro(string $sunrise, string $sunset): float
    {
        $sunriseTime = \DateTime::createFromFormat('h:i A', $sunrise);
        $sunsetTime = \DateTime::createFromFormat('h:i A', $sunset);

        if (!$sunriseTime || !$sunsetTime) {
            return 0;
        }

        $diff = $sunsetTime->diff($sunriseTime);
        return $diff->h + ($diff->i / 60);
    }

    // Fallback si pas de données astro
    private function getFallbackDaylightHours(int $month): float
    {
        // Heures de jour approximatives pour latitude ~45°N (moyenne mondiale)
        $daylightByMonth = [
            1 => 9.0, 2 => 10.5, 3 => 12.0, 4 => 13.5, 5 => 15.0, 6 => 16.0,
            7 => 15.5, 8 => 14.5, 9 => 13.0, 10 => 11.5, 11 => 10.0, 12 => 8.5
        ];

        return $daylightByMonth[$month] ?? 12.0;
    }
}
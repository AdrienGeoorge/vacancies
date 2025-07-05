<?php

namespace App\Command;

use App\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\SyntaxError;
use League\Csv\UnavailableStream;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:import-countries',
    description: 'Importe les codes pays avec leur continent depuis un fichier CSV.',
)]
class ImportCountriesCommand extends Command
{
    private EntityManagerInterface $em;
    private KernelInterface $kernel;

    public function __construct(EntityManagerInterface $em, KernelInterface $kernel)
    {
        parent::__construct();
        $this->em = $em;
        $this->kernel = $kernel;
    }

    /**
     * @throws UnavailableStream
     * @throws InvalidArgument
     * @throws \ReflectionException
     * @throws SyntaxError
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $csvPath = $this->kernel->getProjectDir() . '/public/data/countries_with_continent.csv';
        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);
        $records = Statement::create()->process($csv);

        foreach ($records as $record) {
            $country = new Country();
            $country->setCode($record['code']);
            $country->setName($record['name']);
            $country->setContinent($record['continent']);
            $this->em->persist($country);
        }

        $this->em->flush();

        $output->writeln('<info>Import terminé.</info>');
        return Command::SUCCESS;
    }
}

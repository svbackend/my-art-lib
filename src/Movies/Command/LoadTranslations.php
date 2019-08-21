<?php

namespace App\Movies\Command;

use App\Movies\DTO\MovieTranslationDTO;
use App\Movies\Entity\MovieTranslations;
use App\Movies\Parser\Kinobaza;
use App\Movies\Repository\MovieReleaseDateRepository;
use App\Movies\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadTranslations extends Command
{
    /** @var $repository MovieReleaseDateRepository */
    private $repository;

    /** @var $parser Kinobaza */
    private $parser;

    private $em;

    public function __construct(EntityManagerInterface $em, MovieRepository $repository, Kinobaza $parser, ?string $name = null)
    {
        parent::__construct($name);

        $this->repository = $repository;
        $this->parser = $parser;
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setName('app:load-translations')
            ->setDescription('Find movies without translation for Ukrainian and try to find it')
            ->setHelp('php bin/console app:load-translations');
    }

    protected function execute(InputInterface $i, OutputInterface $o)
    {
        $movies = $this->repository->findAllWithEmptyTranslation('uk');

        foreach ($movies as $movie) {
            if (null === $releaseDate = $movie->getReleaseDate()) {
                $o->writeln(sprintf('Movie "%s" dont have release date. Skipping...', $movie->getOriginalTitle()));
                continue;
            }

            $data = $this->parser->find($movie->getOriginalTitle(), (int)$releaseDate->format('Y'));

            if (!$data) {
                $o->writeln(sprintf('Cant find movie "%s" in kinobaza.com.ua', $movie->getOriginalTitle()));
                continue;
            }

            $data['overview'] .= "\nДжерело https://kinobaza.com.ua";

            $movie->addTranslation(
                new MovieTranslations(
                    $movie,
                    new MovieTranslationDTO('uk', $data['title'], $data['overview'], null)
                )
            );

            $o->writeln("Added translation for {$movie->getOriginalTitle()}");

            $this->em->persist($movie);
            $this->em->flush();
        }
    }
}
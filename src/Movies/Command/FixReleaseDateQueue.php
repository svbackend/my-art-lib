<?php

namespace App\Movies\Command;

use App\Movies\Entity\ReleaseDateQueue;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Repository\ReleaseDateQueueRepository;
use App\Movies\Service\ImdbIdLoaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixReleaseDateQueue extends Command
{
    private $repository;
    private $imdb;
    private $em;

    public function __construct(ImdbIdLoaderService $imdb, ReleaseDateQueueRepository $repository, EntityManagerInterface $em, ?string $name = null)
    {
        parent::__construct($name);

        $this->repository = $repository;
        $this->imdb = $imdb;
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setName('app:fix-release-date-queue')
            ->setDescription('Check inactive queue items to fix them')
            ->setHelp('This command will search for inactive queue items and will try to fix them (load imdb id)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueItems = $this->repository->findAllWithMovies(false)->getResult();

        $i = 0;
        /** @var $queueItem ReleaseDateQueue */
        foreach ($queueItems as $queueItem) {
            $movie = $queueItem->getMovie();
            $tmdbId = $movie->getTmdb()->getId();

            if ($movie->getImdbId()) {
                continue;
            }

            try {
                $imdbId = $this->imdb->getImdbId($tmdbId);
            } catch (TmdbMovieNotFoundException $e) {
                continue;
            }

            if ($imdbId !== null ) {
                $queueItem->activate();
                $movie->setImdbId($imdbId);
            }

            if ($i === 5) {
                $i = 0;
                sleep(5);
            }

            ++$i;
        }

        $this->em->flush();
    }
}

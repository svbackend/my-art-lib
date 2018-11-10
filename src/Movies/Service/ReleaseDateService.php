<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Countries\Repository\CountryRepository;
use App\Movies\Entity\MovieReleaseDate;
use App\Movies\Entity\ReleaseDateQueue;
use App\Movies\Repository\ReleaseDateQueueRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ReleaseDateService
{
    private $em;
    private $repository;
    private $imdbReleaseDateService;
    private $countries;
    private $logger;

    public function __construct(EntityManagerInterface $em, ReleaseDateQueueRepository $repository, ImdbReleaseDateService $imdbReleaseDateService, CountryRepository $countryRepository, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->repository = $repository;
        $this->imdbReleaseDateService = $imdbReleaseDateService;
        $this->countries = $countryRepository->findAll();
        $this->logger = $logger;
    }

    public function runCheck(): void
    {
        $queueItems = $this->repository->findAllWithMovies()->getResult();

        $this->logger->debug('[ReleaseDateService] runCheck', [
            'count' => count($queueItems)
        ]);

        /** @var $queueItem ReleaseDateQueue */
        foreach ($queueItems as $queueItem) {
            $allCountriesHaveReleaseDate = true;
            $movie = $queueItem->getMovie();
            foreach ($this->countries as $country) {
                $releaseDate = $this->imdbReleaseDateService->getReleaseDate($movie, $country);

                $this->logger->debug('[ReleaseDateService] trying to load release date for movie', [
                    'movie' => sprintf('%s with id: %s', $movie->getOriginalTitle(), $movie->getId()),
                    'releaseDate' => $releaseDate ? $releaseDate->format('Y-m-d') : '[EMPTY]',
                ]);

                if ($releaseDate === null) {
                    $allCountriesHaveReleaseDate = false;
                } else {
                    $this->logger->debug(
                        sprintf('[ReleaseDateService] Added new release date %s for movie %s in country %s',
                            $releaseDate->format('Y-m-d'),
                            $movie->getOriginalTitle(),
                            $country->getName()
                        )
                    );
                    $movieReleaseDate = new MovieReleaseDate($movie, $country, $releaseDate);
                    $this->em->persist($movieReleaseDate);
                }
            }

            if ($allCountriesHaveReleaseDate === true) {
                $this->logger->debug(
                    sprintf('[ReleaseDateService] Movie %s removed from queue. All available countries already have release dates',
                        $movie->getOriginalTitle()
                    )
                );
                $this->em->remove($queueItem);
            }
        }

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $exception) {
            // If release date was saved early
            $this->logger->debug("[ReleaseDateService] Exception with message: {$exception->getMessage()}");
        }
    }
}

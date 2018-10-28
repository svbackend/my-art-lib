<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Countries\Entity\Country;
use App\Countries\Repository\CountryRepository;
use App\Movies\Entity\Movie;
use App\Movies\Entity\ReleaseDateQueue;
use App\Movies\Repository\MovieReleaseDateRepository;
use App\Movies\Repository\ReleaseDateQueueRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReleaseDateService
{
    private $em;
    private $repository;
    private $imdbReleaseDateService;
    private $countries;

    public function __construct(EntityManagerInterface $em, ReleaseDateQueueRepository $repository, ImdbReleaseDateService $imdbReleaseDateService, CountryRepository $countryRepository)
    {
        $this->em = $em;
        $this->repository = $repository;
        $this->imdbReleaseDateService = $imdbReleaseDateService;
        $this->countries = $countryRepository->findAll();
    }

    public function runCheck(): void
    {
        $queueItems = $this->repository->findAllWithMovies()->getResult();

        /** @var $queueItem ReleaseDateQueue */
        foreach ($queueItems as $queueItem) {
            $allCountriesHaveReleaseDate = true;
            foreach ($this->countries as $country) {
                $movie = $queueItem->getMovie();
                $releaseDate = $this->imdbReleaseDateService->getReleaseDate($movie, $country);
                if ($releaseDate === null) {
                    $allCountriesHaveReleaseDate = false;
                }
            }

            if ($allCountriesHaveReleaseDate === true) {
                $this->em->remove($queueItem);
            }
        }

        $this->em->flush();
    }
}

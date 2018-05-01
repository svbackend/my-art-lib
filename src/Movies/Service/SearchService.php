<?php
declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Entity\Movie;
use App\Movies\Repository\MovieRepository;

class SearchService
{
    private $repository;
    private $tmdb;
    private $sync;
    private $normalizer;

    public function __construct(MovieRepository $repository, TmdbSearchService $tmdb, TmdbSyncService $sync, TmdbNormalizerService $normalizer)
    {
        $this->repository = $repository;
        $this->tmdb = $tmdb;
        $this->sync = $sync;
        $this->normalizer = $normalizer;
    }

    /**
     * @param string $query
     * @param $locale
     * @return Movie[]|array
     */
    public function findByQuery(string $query, $locale): array
    {
        $movies = $this->repository->search($query);
        if (reset($movies)) {
            return $movies;
        }

        $movies = $this->tmdb->findMoviesByQuery($query, $locale);
        $movies = $this->normalizer->normalizeMoviesToObjects($movies['results'], $locale);
        $this->sync->syncMovies($movies);

        return $movies;
    }
}
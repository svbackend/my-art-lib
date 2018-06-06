<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Entity\Movie;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Pagination\MovieCollection;
use App\Movies\Repository\MovieRepository;
use App\Pagination\PaginatedCollection;
use App\Pagination\PaginatedCollectionInterface;

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
     * @param string   $query
     * @param string   $locale
     * @param int      $offset
     * @param int|null $limit
     *
     * @throws \Exception
     *
     * @return PaginatedCollectionInterface
     */
    public function findByQuery(string $query, string $locale, int $offset = 0, ?int $limit = null): PaginatedCollectionInterface
    {
        $moviesQuery = $this->repository->findByTitleQuery($query);
        $movies = new PaginatedCollection($moviesQuery, $offset, $limit);
        if ($movies->getTotal() > 0) {
            return $movies;
        }

        $movies = $this->tmdb->findMoviesByQuery($query, $locale);
        $totalResults = (int) $movies['total_results'];

        if (0 === $totalResults) {
            return new MovieCollection([], 0, $offset);
        }

        // If we have a lot of movies then save it all
        if (isset($movies['total_pages']) && $movies['total_pages'] > 1) {
            // $i = 2 because $movies currently already has movies from page 1
            for ($i = 2; $i <= $movies['total_pages']; ++$i) {
                $moviesOnPage = $this->tmdb->findMoviesByQuery($query, $locale, [
                    'page' => $i,
                ]);
                $moviesObjectsOnPage = $this->normalizer->normalizeMoviesToObjects($moviesOnPage['results'], $locale);
                $this->sync->syncMovies($moviesObjectsOnPage);
            }
        }

        $movies = $this->normalizer->normalizeMoviesToObjects($movies['results'], $locale);
        $this->sync->syncMovies($movies);

        return new MovieCollection($movies, $totalResults, $offset);
    }

    /**
     * @param int    $tmdb_id
     * @param string $locale
     *
     * @throws \Exception
     *
     * @return Movie|null
     */
    public function findByTmdbId(int $tmdb_id, string $locale): ?Movie
    {
        try {
            $movie = $this->tmdb->findMovieById($tmdb_id, $locale);
        } catch (TmdbMovieNotFoundException $exception) {
            return null;
        }

        $movies = $this->normalizer->normalizeMoviesToObjects([$movie], $locale);

        return reset($movies) ?: null;
    }
}

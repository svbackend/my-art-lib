<?php

declare(strict_types=1);

namespace App\Movies\Service;

class ImdbIdLoaderService
{
    private $searchService;

    public function __construct(TmdbSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /***
     * @throws
     */
    public function getImdbId(int $tmdbId): ?string
    {
        $movie = $this->searchService->findMovieById($tmdbId);

        return $movie['imdb_id'] ?? null;
    }
}

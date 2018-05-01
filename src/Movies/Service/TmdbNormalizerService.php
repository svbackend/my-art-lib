<?php
declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Entity\Movie;

class TmdbNormalizerService
{
    private $movieManageService;
    private const IMAGE_HOST = 'https://image.tmdb.org/t/p/original';

    public function __construct(MovieManageService $movieManageService)
    {
        $this->movieManageService = $movieManageService;
    }

    /**
     * @param array $movies
     * @param string $locale
     * @return Movie[]
     */
    public function normalizeMoviesToObjects(array $movies, string $locale = 'en'): array
    {
        $normalizedMovies = [];
        foreach ($movies as $movie) {
            $movieArray = [
                'originalTitle' => $movie['original_title'],
                'originalPosterUrl' => self::IMAGE_HOST . $movie['poster_path'],
            ];
            if (isset($movie['release_date'])) $movieArray['releaseDate'] = $movie['release_date'];

            $tmdbArray = [
                'id' => $movie['id']
            ];
            if (isset($movie['vote_average'])) $tmdbArray['voteAverage'] = $movie['vote_average'];
            if (isset($movie['vote_count'])) $tmdbArray['voteCount'] = $movie['vote_count'];

            $translation = [
                'locale' => $locale,
                'title' => $movie['title'],
                'posterUrl' => $movieArray['originalPosterUrl'],
                'overview' => $movie['overview']
            ];

            $movieObject = $this->movieManageService->createMovie($movieArray, $tmdbArray, [], [$translation]);
            $normalizedMovies[] = $movieObject;
        }

        return $normalizedMovies;
    }
}
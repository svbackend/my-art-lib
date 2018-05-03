<?php
declare(strict_types=1);

namespace App\Movies\Service;

use App\Genres\Repository\GenreRepository;
use App\Movies\DTO\MovieDTO;
use App\Movies\DTO\MovieTranslationDTO;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTMDB;

class TmdbNormalizerService
{
    private $movieManageService;
    private $genreRepository;
    private const IMAGE_HOST = 'https://image.tmdb.org/t/p/original';

    public function __construct(MovieManageService $movieManageService, GenreRepository $genreRepository)
    {
        $this->movieManageService = $movieManageService;
        $this->genreRepository = $genreRepository;
    }

    /**
     * @param array $movies
     * @param string $locale
     * @throws \Exception
     * @return Movie[]
     */
    public function normalizeMoviesToObjects(array $movies, string $locale = 'en'): array
    {
        $normalizedMovies = [];
        foreach ($movies as $movie) {
            $movieDTO = new MovieDTO(
                $movie['original_title'],
                self::IMAGE_HOST . $movie['poster_path'],
                null,
                null,
                null,
                $movie['release_date'] ?? null
            );
            $tmdb = new MovieTMDB((int)$movie['id'], null, null);
            $locale = isset($movie['locale']) ? substr($movie['locale'], 0, 2) : $locale; // "en-US" to "en"

            $movieObject = $this->movieManageService->createMovieByDTO($movieDTO, $tmdb, [], [
                new MovieTranslationDTO($locale, $movie['title'], $movie['overview'], null)
            ]);

            $genres = $this->genreRepository->findByTmdbIds($movie['genre_ids']);
            foreach ($genres as $genre) {
                $movieObject->addGenre($genre);
            }

            $normalizedMovies[] = $movieObject;
        }

        return $normalizedMovies;
    }
}
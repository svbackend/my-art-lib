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
            $movieDTO = $this->createMovieDTO($movie);
            $tmdb = $this->createMovieTmdbDTO($movie);
            $locale = $this->getMovieLocale($movie, $locale);

            $movieObject = $this->movieManageService->createMovieByDTO($movieDTO, $tmdb, [], [
                $this->createMovieTranslation($locale, $movie)
            ]);

            $genresIds = $this->getGenresIds($movie);
            $movieObject = $this->addGenres($movieObject, $genresIds);

            $normalizedMovies[] = $movieObject;
        }

        return $normalizedMovies;
    }

    /**
     * @param array $movie
     * @return MovieDTO
     * @throws \Exception
     */
    private function createMovieDTO(array $movie): MovieDTO
    {
        return new MovieDTO(
            $movie['original_title'],
            self::IMAGE_HOST . $movie['poster_path'],
            $movie['imdb_id'] ?? null,
            isset($movie['budget']) ? (int)$movie['budget'] : null,
            isset($movie['runtime']) ? (int)$movie['runtime'] : null,
            isset($movie['release_date']) ? $movie['release_date'] : null
        );
    }

    private function createMovieTmdbDTO(array $movie): MovieTMDB
    {
        return new MovieTMDB(
            (int)$movie['id'],
            isset($movie['vote_average']) ? (float)$movie['vote_average'] : null,
            isset($movie['vote_count']) ? (int)$movie['vote_count'] : null
        );
    }

    private function createMovieTranslation(string $locale, array $movie)
    {
        return new MovieTranslationDTO($locale, $movie['title'], $movie['overview'], null);
    }

    private function addGenres(Movie $movie, array $tmdbGenresIds): Movie
    {
        $genres = $this->genreRepository->findByTmdbIds($tmdbGenresIds);
        foreach ($genres as $genre) {
            $movie->addGenre($genre);
        }

        return $movie;
    }

    private function getGenresIds(array $movie): array
    {
        if (isset($movie['genres'])) {
            $movie['genre_ids'] = array_map(function ($genre) {
                return $genre['id'];
            }, $movie['genres']);
        }

        return $movie['genre_ids'] ?? [];
    }

    private function getMovieLocale(array $movie, string $defaultLocale)
    {
        return isset($movie['locale']) ? substr($movie['locale'], 0, 2) : $defaultLocale; // "en-US" to "en"
    }
}
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
     * @param array  $movies
     * @param string $locale
     * @return \Iterator
     * @throws \ErrorException
     */
    public function normalizeMoviesToObjects(array $movies, string $locale = 'en'): \Iterator
    {
        foreach ($movies as $movie) {
            $movieDTO = $this->createMovieDTO($movie);
            $tmdb = $this->createMovieTmdbDTO($movie);
            $locale = $this->getMovieLocale($movie, $locale);

            $movieObject = $this->movieManageService->createMovieByDTO($movieDTO, $tmdb, [], [
                $this->createMovieTranslationDTO($locale, $movie),
            ]);

            $genresIds = $this->getGenresIds($movie);
            $movieObject = $this->addGenres($movieObject, $genresIds);

            yield $movieObject;
        }
    }

    /**
     * @param array $movie
     *
     * @throws \Exception
     *
     * @return MovieDTO
     */
    private function createMovieDTO(array $movie): MovieDTO
    {
        return new MovieDTO(
            $movie['original_title'],
            isset($movie['poster_path']) ? self::IMAGE_HOST.$movie['poster_path'] : '',
            $movie['imdb_id'] ?? null,
            isset($movie['budget']) ? (int) $movie['budget'] : null,
            isset($movie['runtime']) ? (int) $movie['runtime'] : null,
            isset($movie['release_date']) ? $movie['release_date'] : null
        );
    }

    private function createMovieTmdbDTO(array $movie): MovieTMDB
    {
        return new MovieTMDB(
            (int) $movie['id'],
            isset($movie['vote_average']) ? (float) $movie['vote_average'] : null,
            isset($movie['vote_count']) ? (int) $movie['vote_count'] : null
        );
    }

    private function createMovieTranslationDTO(string $locale, array $movie): MovieTranslationDTO
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
        return isset($movie['locale']) ? mb_substr($movie['locale'], 0, 2) : $defaultLocale; // "en-US" to "en"
    }
}

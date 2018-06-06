<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Genres\Entity\Genre;
use App\Genres\Repository\GenreRepository;
use App\Movies\DTO\MovieDTO;
use App\Movies\DTO\MovieTranslationDTO;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTMDB;
use App\Movies\Entity\MovieTranslations;
use App\Movies\Request\CreateMovieRequest;

class MovieManageService
{
    private $genreRepository;

    public function __construct(GenreRepository $genreRepository)
    {
        $this->genreRepository = $genreRepository;
    }

    /**
     * @param CreateMovieRequest $request
     *
     * @throws \Exception
     *
     * @return Movie
     */
    public function createMovieByRequest(CreateMovieRequest $request): Movie
    {
        $movie = $request->get('movie');
        $tmdb = $movie['tmdb'];
        $movie['genres'] = array_map(function ($genre) { return $genre['id']; }, $movie['genres']);
        $genres = $this->getGenresByIds($movie['genres']);

        $translations = array_map(function (array $translation) {
            return new MovieTranslationDTO(
                $translation['locale'],
                $translation['title'],
                $translation['overview'] ?? null,
                $translation['posterUrl'] ?? null
            );
        }, $movie['translations']);

        $movieTMDB = new MovieTMDB(
            $tmdb['id'],
            $tmdb['voteAverage'] ?? null,
            $tmdb['voteCount'] ?? null
        );

        $movieDTO = new MovieDTO(
            $movie['originalTitle'],
            $movie['originalPosterUrl'],
            $movie['imdbId'] ?? null,
            $movie['budget'] ?? null,
            $movie['runtime'] ?? null,
            $movie['releaseDate'] ?? null
        );

        return $this->createMovieByDTO($movieDTO, $movieTMDB, $genres, $translations);
    }

    /**
     * @param MovieDTO              $movieDTO
     * @param MovieTMDB             $movieTMDB
     * @param Genre[]               $genres
     * @param MovieTranslationDTO[] $translations
     *
     * @throws \ErrorException
     *
     * @return Movie
     */
    public function createMovieByDTO(MovieDTO $movieDTO, MovieTMDB $movieTMDB, array $genres, array $translations): Movie
    {
        $movie = new Movie($movieDTO, $movieTMDB);

        foreach ($genres as $genre) {
            $movie->addGenre($genre);
        }

        $addTranslation = function (MovieTranslationDTO $translation) use ($movie) {
            $movie->addTranslation(
                new MovieTranslations($movie, $translation)
            );
        };

        $movie->updateTranslations($translations, $addTranslation);

        return $movie;
    }

    /**
     * @param array $ids
     *
     * @return Genre[]
     */
    private function getGenresByIds(array $ids)
    {
        return $this->genreRepository->findBy(['id' => $ids]);
    }
}

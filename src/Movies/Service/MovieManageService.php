<?php
declare(strict_types=1);

namespace App\Movies\Service;

use App\Genres\Entity\Genre;
use App\Genres\Repository\GenreRepository;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTMDB;
use App\Movies\Entity\MovieTranslations;
use App\Movies\Request\CreateMovieRequest;
use Doctrine\ORM\EntityManagerInterface;

class MovieManageService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createMovieByRequest(CreateMovieRequest $request): Movie
    {
        $movie = $request->get('movie');
        return $this->createMovie($movie, $movie['tmdb'], $movie['genres'], $movie['translations']);
    }

    public function createMovie(array $movieArray, array $tmdbArray, array $genres, array $translations): Movie
    {
        $tmdb = new MovieTMDB($tmdbArray['id']);
        if (isset($tmdbArray['voteAverage'])) $tmdb->setVoteAverage($tmdbArray['voteAverage']);
        if (isset($tmdbArray['voteCount'])) $tmdb->setVoteCount($tmdbArray['voteCount']);

        $movie = new Movie($movieArray['originalTitle'], $movieArray['originalPosterUrl'], $tmdb);
        if (isset($movieArray['imdbId'])) $movie->setImdbId($movieArray['imdbId']);
        if (isset($movieArray['budget'])) $movie->setBudget($movieArray['budget']);
        if (isset($movieArray['runtime'])) $movie->setRuntime($movieArray['runtime']);
        if (isset($movieArray['releaseDate'])) $movie->setReleaseDate(new \DateTimeImmutable($movieArray['releaseDate']));

        /**
         * @var $genresRepository GenreRepository
         */
        $genresRepository = $this->entityManager->getRepository(Genre::class);
        $genres = $genresRepository->findBy([
            'id' => array_map(function ($genre) { return $genre['id']; }, $genres)
        ]);
        foreach ($genres as $genre) {
            $movie->addGenre($genre);
        }

        $addTranslation = function ($translation) use ($movie) {
            $movie->addTranslation(
                new MovieTranslations($movie, $translation['locale'], $translation['title'], $translation['posterUrl'], $translation['overview'])
            );
        };

        $movie->updateTranslations($translations, $addTranslation);
        $this->entityManager->persist($movie);

        return $movie;
    }
}
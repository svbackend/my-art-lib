<?php

namespace App\Movies\DataFixtures;

use App\Genres\Entity\Genre;
use App\Genres\Entity\GenreTranslations;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTMDB;
use App\Movies\Entity\MovieTranslations;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class MoviesFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tmdb = new MovieTMDB(1);
        $tmdb->setVoteAverage(7.8);
        $tmdb->setVoteCount(100);

        $movie = new Movie('Original Title', 'http://placehold.it/320x480', $tmdb);
        $movie
            ->addTranslation(new MovieTranslations($movie, 'en', 'Original Title (en)', 'http://placehold.it/480x320', 'Overview (en)'))
            ->addTranslation(new MovieTranslations($movie, 'uk', 'Оригінальная назва (uk)', 'http://placehold.it/480x320', 'Overview (uk)'))
            ->addTranslation(new MovieTranslations($movie, 'ru', 'Оригинальное название (ru)', 'http://placehold.it/480x320', 'Overview (ru)'));
        $movie->setReleaseDate(new \DateTimeImmutable('-10 years'));
        $movie->setRuntime(100);
        $movie->setBudget(60000);
        $movie->setImdbId('imdb-test-id');

        $testGenre = new Genre();
        $testGenre
            ->addTranslation(new GenreTranslations($testGenre, 'en', 'Test Genre (en)'))
            ->addTranslation(new GenreTranslations($testGenre, 'uk', 'Test Genre (uk)'))
            ->addTranslation(new GenreTranslations($testGenre, 'ru', 'Test Genre (ru)'));

        $movie->addGenre($testGenre);

        $manager->persist($movie);
        $manager->flush();
    }
}
<?php

namespace App\Movies\DataFixtures;

use App\Genres\Entity\Genre;
use App\Genres\Entity\GenreTranslations;
use App\Movies\DTO\MovieDTO;
use App\Movies\DTO\MovieTranslationDTO;
use App\Movies\Entity\MovieTMDB;
use App\Movies\Service\MovieManageService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class MoviesFixtures extends Fixture
{
    const MOVIE_TITLE = 'zMs1Os7qwEqWxXvb';
    const MOVIE_TMDB_ID = 1;
    const MOVIE_TMDB_ID_2 = 2;

    private $movieManageService;

    public function __construct(MovieManageService $movieManageService)
    {
        $this->movieManageService = $movieManageService;
    }

    /**
     * @param ObjectManager $manager
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $movieTitle = self::MOVIE_TITLE;
        $movieDTO = new MovieDTO($movieTitle, 'http://placehold.it/320x480', 'imdb-test-id', 60000, 100, '-10 years');
        $tmdb = new MovieTMDB(self::MOVIE_TMDB_ID, 7.8, 100);
        $tmdb2 = new MovieTMDB(self::MOVIE_TMDB_ID_2, 7.8, 100);

        $testGenre = new Genre();
        $testGenre
            ->addTranslation(new GenreTranslations($testGenre, 'en', 'Test Genre (en)'))
            ->addTranslation(new GenreTranslations($testGenre, 'uk', 'Test Genre (uk)'))
            ->addTranslation(new GenreTranslations($testGenre, 'ru', 'Test Genre (ru)'));

        $movie = $this->movieManageService->createMovieByDTO($movieDTO, $tmdb, [$testGenre], [
            new MovieTranslationDTO('en', "$movieTitle (en)", 'Overview (en)', 'http://placehold.it/480x320'),
            new MovieTranslationDTO('uk', "$movieTitle (uk)", 'Overview (uk)', 'http://placehold.it/480x320'),
            new MovieTranslationDTO('ru', "$movieTitle (ru)", 'Overview (ru)', 'http://placehold.it/480x320'),
        ]);

        $movie2 = $this->movieManageService->createMovieByDTO($movieDTO, $tmdb2, [$testGenre], [
            new MovieTranslationDTO('en', "$movieTitle 2 (en)", 'Overview (en)', 'http://placehold.it/480x320'),
            new MovieTranslationDTO('uk', "$movieTitle 2 (uk)", 'Overview (uk)', 'http://placehold.it/480x320'),
            new MovieTranslationDTO('ru', "$movieTitle 2 (ru)", 'Overview (ru)', 'http://placehold.it/480x320'),
        ]);

        $manager->persist($testGenre);
        $manager->persist($movie);
        $manager->persist($movie2);
        $manager->flush();
    }
}

<?php

namespace App\Movies\DataFixtures;

use App\Actors\Entity\Actor;
use App\Actors\Entity\ActorTMDB;
use App\Actors\Entity\ActorTranslations;
use App\Genres\Entity\Genre;
use App\Genres\Entity\GenreTranslations;
use App\Movies\DTO\MovieDTO;
use App\Movies\DTO\MovieTranslationDTO;
use App\Movies\Entity\MovieTMDB;
use App\Movies\Service\MovieManageService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class MoviesFixtures extends Fixture
{
    const MOVIE_1_ID = 1;
    const MOVIE_2_ID = 2;
    const MOVIE_TITLE = 'zMs1Os7qwEqWxXvb';
    const MOVIE_TMDB_ID = 1;
    const MOVIE_TMDB_ID_2 = 2;
    const MOVIE_ACTOR_TMDB_ID = 91238;

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
        if ($manager instanceof EntityManager === false) {
            throw new \InvalidArgumentException('MoviesFixtures $manager should be instance of EntityManager');
        }
        /* @var $manager EntityManager */

        $movieTitle = self::MOVIE_TITLE;
        $movieDTO1 = new MovieDTO($movieTitle, 'http://placehold.it/320x480', 'imdb-test-id1', 60000, 100, '01.01.2009');
        $movieDTO2 = new MovieDTO($movieTitle, 'http://placehold.it/320x480', 'imdb-test-id2', 60000, 100, '01.01.2019');
        $tmdb = new MovieTMDB(self::MOVIE_TMDB_ID, 7.8, 100);
        $tmdb2 = new MovieTMDB(self::MOVIE_TMDB_ID_2, 7.8, 100);

        $testGenre = new Genre();
        $testGenre
            ->addTranslation(new GenreTranslations($testGenre, 'en', 'Test Genre (en)'))
            ->addTranslation(new GenreTranslations($testGenre, 'uk', 'Test Genre (uk)'))
            ->addTranslation(new GenreTranslations($testGenre, 'ru', 'Test Genre (ru)'));

        $actor = new Actor('Test MovieActor', new ActorTMDB(self::MOVIE_ACTOR_TMDB_ID));
        $actor->addTranslation(new ActorTranslations($actor, 'en', 'Test MovieActor (en)'));

        $movie = $this->movieManageService->createMovieByDTO($movieDTO1, $tmdb, [$testGenre], [
            new MovieTranslationDTO('en', "$movieTitle (en)", 'Overview (en)', 'http://placehold.it/320x480'),
            new MovieTranslationDTO('uk', "$movieTitle (uk)", 'Overview (uk)', 'http://placehold.it/320x480'),
            new MovieTranslationDTO('ru', "$movieTitle (ru)", 'Overview (ru)', 'http://placehold.it/320x480'),
        ]);
        $movie->addActor($actor);

        $movie2 = $this->movieManageService->createMovieByDTO($movieDTO2, $tmdb2, [$testGenre], [
            new MovieTranslationDTO('en', "$movieTitle 2 (en)", 'Overview (en)', 'http://placehold.it/320x480'),
            new MovieTranslationDTO('uk', "$movieTitle 2 (uk)", 'Overview (uk)', 'http://placehold.it/320x480'),
            new MovieTranslationDTO('ru', "$movieTitle 2 (ru)", 'Overview (ru)', 'http://placehold.it/320x480'),
        ]);
        $movie2->addActor($actor);

        $manager->getConnection()->exec("ALTER SEQUENCE movies_id_seq RESTART WITH 1; UPDATE movies SET id=nextval('movies_id_seq');");

        $manager->persist($testGenre);
        $manager->persist($actor);
        $manager->persist($movie);
        $manager->persist($movie2);
        $manager->flush();
    }
}

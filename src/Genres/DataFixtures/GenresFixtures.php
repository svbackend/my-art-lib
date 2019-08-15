<?php

namespace App\Genres\DataFixtures;

use App\Genres\Entity\Genre;
use App\Genres\Entity\GenreTranslations;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class GenresFixtures extends Fixture
{
    public const GENRE_DRAMA_ID = 1;
    public const GENRE_DRAMA_TMDB_ID = 18;
    public const GENRE_COMEDY_ID = 2;
    public const GENRE_CRIMINAL_ID = 3;

    public function load(ObjectManager $manager): void
    {
        if ($manager instanceof EntityManager === false) {
            throw new \InvalidArgumentException('MoviesFixtures $manager should be instance of EntityManager');
        }
        /* @var $manager EntityManager */
        $manager->getConnection()->exec("ALTER SEQUENCE genres_id_seq RESTART WITH 1; UPDATE genres SET id=nextval('genres_id_seq');");

        $drama = new Genre(self::GENRE_DRAMA_TMDB_ID); // https://www.themoviedb.org/genre/18-drama/movie
        $drama
            ->addTranslation(new GenreTranslations($drama, 'en', 'Drama'))
            ->addTranslation(new GenreTranslations($drama, 'uk', 'Драма'))
            ->addTranslation(new GenreTranslations($drama, 'ru', 'Драма'));

        $comedy = new Genre();
        $comedy
            ->addTranslation(new GenreTranslations($comedy, 'en', 'Comedy'))
            ->addTranslation(new GenreTranslations($comedy, 'uk', 'Комедия'))
            ->addTranslation(new GenreTranslations($comedy, 'ru', 'Комедія'));

        $criminal = new Genre();
        $criminal
            ->addTranslation(new GenreTranslations($criminal, 'en', 'Criminal'))
            ->addTranslation(new GenreTranslations($criminal, 'uk', 'Криминал'))
            ->addTranslation(new GenreTranslations($criminal, 'ru', 'Кримінал'));

        $manager->persist($drama);
        $manager->persist($comedy);
        $manager->persist($criminal);
        $manager->flush();
    }
}

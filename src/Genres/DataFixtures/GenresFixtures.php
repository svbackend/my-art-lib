<?php

namespace App\Genres\DataFixtures;

use App\Genres\Entity\Genre;
use App\Genres\Entity\GenreTranslations;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class GenresFixtures extends Fixture
{
    const GENRE_TMDB_ID = 18;

    public function load(ObjectManager $manager): void
    {
        $drama = new Genre(self::GENRE_TMDB_ID); // https://www.themoviedb.org/genre/18-drama/movie
        $drama
            ->addTranslation(new GenreTranslations($drama, 'en', 'Drama'))
            ->addTranslation(new GenreTranslations($drama, 'uk', 'Драма'))
            ->addTranslation(new GenreTranslations($drama, 'ru', 'Драма'));

        $manager->persist($drama);
        $manager->flush();
    }
}
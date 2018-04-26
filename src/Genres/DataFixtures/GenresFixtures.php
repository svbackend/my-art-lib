<?php

namespace App\Genres\DataFixtures;

use App\Genres\Entity\Genre;
use App\Genres\Entity\GenreTranslations;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class GenresFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $comedy = new Genre();
        $comedy
            ->addTranslation(new GenreTranslations($comedy, 'en', 'Comedy'))
            ->addTranslation(new GenreTranslations($comedy, 'uk', 'Комедія'))
            ->addTranslation(new GenreTranslations($comedy, 'ru', 'Комедия'));

        $manager->persist($comedy);
        $manager->flush();
    }
}
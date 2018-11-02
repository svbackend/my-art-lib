<?php

namespace App\Users\DataFixtures;

use App\Countries\DataFixtures\CountriesFixtures;
use App\Countries\Entity\Country;
use App\Movies\DataFixtures\MoviesFixtures;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieReleaseDate;
use App\Users\Entity\User;
use App\Users\Entity\UserInterestedMovie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class UsersMoviesFixtures extends Fixture implements DependentFixtureInterface
{
    const RELEASE_DATE = '2018-01-01';
    const MOVIE_ID = MoviesFixtures::MOVIE_1_ID;

    /**
     * @param ObjectManager $manager
     *
     * @throws
     */
    public function load(ObjectManager $manager): void
    {
        $movie = $manager->find(Movie::class, self::MOVIE_ID);
        $ukr = $manager->find(Country::class, CountriesFixtures::COUNTRY_UKR_ID);
        $user = $manager->find(User::class, UsersFixtures::MOVIE_TESTER_ID);
        $date = new \DateTime(self::RELEASE_DATE);
        $releaseDate = new MovieReleaseDate($movie, $ukr, $date);

        $userInterestedMovie = new UserInterestedMovie($user, $movie);

        $manager->persist($releaseDate);
        $manager->persist($userInterestedMovie);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [UsersFixtures::class, MoviesFixtures::class, CountriesFixtures::class];
    }
}

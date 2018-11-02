<?php

namespace App\Countries\DataFixtures;

use App\Countries\Entity\Country;
use App\Countries\Entity\ImdbCountry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class CountriesFixtures extends Fixture
{
    const COUNTRY_UKR_ID = 1;
    const COUNTRY_UKR_CODE = 'UKR';
    const COUNTRY_POL_ID = 2;
    const COUNTRY_POL_CODE = 'POL';

    public function load(ObjectManager $manager): void
    {
        if ($manager instanceof EntityManager === false) {
            throw new \InvalidArgumentException('UsersFixtures $manager should be instance of EntityManager');
        }
        /* @var $manager EntityManager */

        $ukr = new Country('Ukraine', 'UKR');
        $pol = new Country('Poland', 'POL');
        $imdbUkr = new ImdbCountry($ukr);
        $imdbPol = new ImdbCountry($pol);

        $manager->getConnection()->exec("ALTER SEQUENCE countries_id_seq RESTART WITH 1; UPDATE countries SET id=nextval('countries_id_seq');");

        $manager->persist($ukr);
        $manager->persist($pol);
        $manager->persist($imdbUkr);
        $manager->persist($imdbPol);
        $manager->flush();
    }
}

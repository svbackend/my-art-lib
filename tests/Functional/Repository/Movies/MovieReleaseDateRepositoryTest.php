<?php

namespace App\Tests\Functional\Movies\Repository;

use App\Movies\Entity\MovieReleaseDate;
use App\Movies\Repository\MovieReleaseDateRepository;
use App\Users\DataFixtures\UsersFixtures;
use App\Users\DataFixtures\UsersMoviesFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MovieReleaseDateRepositoryTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /** @var $repository MovieReleaseDateRepository */
    private $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(MovieReleaseDate::class);
    }

    public function testGetAllTodayReleaseDatesForNotifications()
    {
        $query = $this->repository->findAllByDate(UsersMoviesFixtures::RELEASE_DATE);
        $rows = $query->getScalarResult();

        $row = reset($rows);

        self::assertSame(UsersFixtures::MOVIE_TESTER_ID, $row['u_id']);
        self::assertSame(UsersFixtures::MOVIE_TESTER_EMAIL, $row['u_email']);
        self::assertSame(UsersFixtures::MOVIE_TESTER_COUNTRY_CODE, $row['c_code']);
        self::assertSame(UsersMoviesFixtures::MOVIE_ID, $row['m_id']);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}

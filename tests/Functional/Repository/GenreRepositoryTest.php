<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Genre;
use App\Entity\Translations\GenreTranslations;
use App\Repository\GenreRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\ORMException;
use function PHPSTORM_META\type;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GenreRepositoryTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var Genre
     */
    private $genre;

    /**
     * @var GenreRepository
     */
    private $genreRepository;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->genreRepository = $this->entityManager->getRepository(Genre::class);
    }

    private function createGenre()
    {
        $this->genre = new Genre();
        $this->genre
            ->addTranslation(new GenreTranslations($this->genre, 'en', 'TestGenre'))
            ->addTranslation(new GenreTranslations($this->genre, 'ru', 'Тестовый жанр'))
            ->addTranslation(new GenreTranslations($this->genre, 'uk', 'Тестовий жанр'));
        $this->entityManager->persist($this->genre);
        $this->entityManager->flush();
    }

    public function testGetGenreWithTranslations()
    {
        $this->createGenre();
        $testGenreId = $this->genre->getId();
        $this->entityManager->clear();

        $genre = $this->genreRepository->find($testGenreId);

        self::assertEquals('TestGenre', $genre->getTranslation('en')->getName());
        self::assertEquals('Тестовый жанр', $genre->getTranslation('ru')->getName());
        self::assertEquals('Тестовий жанр', $genre->getTranslation('uk')->getName());
        self::assertEquals('TestGenre', $genre->getTranslation('WRONG_LOCALE')->getName());
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
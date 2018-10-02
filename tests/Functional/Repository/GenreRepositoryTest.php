<?php

namespace App\Tests\Functional\Repository;

use App\Genres\Entity\Genre;
use App\Genres\Entity\GenreTranslations;
use App\Genres\Repository\GenreRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GenreRepositoryTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \App\Genres\Entity\Genre
     */
    private $genre;

    /**
     * @var GenreRepository
     */
    private $genreRepository;

    /**
     * {@inheritdoc}
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

        self::assertSame('TestGenre', $genre->getTranslation('en')->getName());
        self::assertSame('Тестовый жанр', $genre->getTranslation('ru')->getName());
        self::assertSame('Тестовий жанр', $genre->getTranslation('uk')->getName());
        self::assertInstanceOf(GenreTranslations::class, $genre->getTranslation('WRONG_LOCALE'));
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

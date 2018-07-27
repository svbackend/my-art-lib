<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Genres\Entity\Genre;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTMDB;
use App\Movies\EventListener\MovieSyncProcessor;
use Doctrine\ORM\EntityManager;
use Enqueue\Client\ProducerInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MovieSyncProcessorTest extends KernelTestCase
{
    /** @var EntityManager|MockObject */
    private $em;

    /** @var PsrContext */
    private $psrContext;

    /** @var PsrMessage|MockObject */
    private $psrMessage;

    /** @var MovieSyncProcessor */
    private $movieSyncProcessor;

    /** @var ProducerInterface */
    private $producer;

    /**
     * @throws \ReflectionException
     */
    public function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->psrContext = $this->createMock(PsrContext::class);
        $this->psrMessage = $this->createMock(PsrMessage::class);
        $this->producer = $this->createMock(ProducerInterface::class);
        $this->movieSyncProcessor = new MovieSyncProcessor($this->em, $this->producer);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    public function testThatAllMoviesWillBeCorrectlySaved()
    {
        /** @var $genre Genre|MockObject */
        $genre = $this->createMock(Genre::class);
        $genre->method('getId')->willReturn(2);

        /** @var $movie Movie|MockObject */
        $movie = $this->createMock(Movie::class);
        $movie->method('getGenres')->willReturn([$genre]);
        $movieTmdb = $this->createMock(MovieTMDB::class);
        $movieTmdb->method('getId')->willReturn(1);
        $movie->method('getTmdb')->willReturn($movieTmdb);

        $movies = serialize([$movie]);

        $this->em->method('getReference')->will($this->returnValueMap([
            [Genre::class, $genre->getId(), $genre],
        ]));
        $this->psrMessage->method('getBody')->willReturn($movies);
        $this->psrMessage->method('getProperty')->with('load_similar', true)->willReturn(false);

        $persistedEntities = [];
        $this->em->method('persist')->will($this->returnCallback(function ($entity) use (&$persistedEntities) {
            $persistedEntities[] = $entity;
            return true;
        }));

        $this->em->expects($this->once())->method('flush');
        $this->movieSyncProcessor->process($this->psrMessage, $this->psrContext);

        $persistedEntitiesCount = count($persistedEntities);
        self::assertEquals(1, $persistedEntitiesCount); // Only Movie (without genre, because genre already saved)

        $incorrectEntities = array_filter($persistedEntities, function ($entity) {
            // We should persist only Movie so any other entities are incorrect
            return $entity instanceof Movie === false;
        });

        if (count($incorrectEntities) > 0) {
            $this->fail('Some of your entities are persisted instead of just be associated through reference');
        }

        /** @var $newMovie Movie */
        $newMovie = reset($persistedEntities);
        $genresReferences = $newMovie->getGenres();
        $genreReference = reset($genresReferences);

        self::assertEquals($genre->getId(), $genreReference->getId());
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    public function testThatAllMoviesWithGenresWillBeCorrectlySaved()
    {
        /** @var $genre Genre|MockObject */
        $genre = $this->createMock(Genre::class);
        $genre->method('getId')->willReturn(null);

        /** @var $movie Movie|MockObject */
        $movie = $this->createMock(Movie::class);
        $movie->method('getGenres')->willReturn([$genre]);
        $movieTmdb = $this->createMock(MovieTMDB::class);
        $movieTmdb->method('getId')->willReturn(1);
        $movie->method('getTmdb')->willReturn($movieTmdb);

        $movies = serialize([$movie]);
        $this->psrMessage->method('getBody')->willReturn($movies);
        $this->psrMessage->method('getProperty')->with('load_similar', true)->willReturn(false);

        $persistedEntities = [];
        $this->em->method('persist')->will($this->returnCallback(function ($entity) use (&$persistedEntities) {
            $persistedEntities[] = $entity;
            return true;
        }));

        $this->em->expects($this->once())->method('flush');
        $this->movieSyncProcessor->process($this->psrMessage, $this->psrContext);

        $persistedEntitiesCount = count($persistedEntities);
        self::assertEquals(2, $persistedEntitiesCount); // Movie + Genre

        $incorrectEntities = array_filter($persistedEntities, function ($entity) {
            return $entity instanceof Genre === false && $entity instanceof Movie === false;
            // Not working: return in_array(get_class($entity), [Genre::class, Movie::class]) === false;
        });

        if (count($incorrectEntities) > 0) {
            $this->fail('Some of your entities are persisted instead of just be associated through reference');
        }
    }
}
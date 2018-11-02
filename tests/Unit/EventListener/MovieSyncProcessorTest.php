<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Movies\Entity\Movie;
use App\Movies\EventListener\MovieSyncProcessor;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbNormalizerService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    /** @var MockObject|ProducerInterface */
    private $producer;

    /**
     * @var MockObject|TmdbNormalizerService
     */
    private $tmdbNormalizer;
    private $logger;

    /**
     * @var MockObject|MovieRepository
     */
    private $repository;
    private $dispatcher;

    protected function setUp()
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->psrContext = $this->createMock(PsrContext::class);
        $this->psrMessage = $this->createMock(PsrMessage::class);
        $this->producer = $this->createMock(ProducerInterface::class);
        $this->tmdbNormalizer = $this->createMock(TmdbNormalizerService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(MovieRepository::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    private function getMoviesIterator($movie)
    {
        yield $movie;
    }

    public function testThatAllMoviesWillBeSaved()
    {
        $movie = json_encode([
            'id' => 1,
            'original_title' => 'Test Title',
        ]);
        $this->psrMessage->method('getBody')->willReturn($movie);
        $this->psrMessage->method('getProperty')->with(MovieSyncProcessor::PARAM_LOAD_SIMILAR_MOVIES, false)->willReturn(true);

        $this->repository->method('findOneByIdOrTmdbId')->willReturn(null);

        $newMovie = $this->createMock(Movie::class);
        $newMovie->method('getId')->willReturn(123);
        $this->tmdbNormalizer->method('normalizeMoviesToObjects')->willReturn($this->getMoviesIterator($newMovie));

        $persistedEntity = null;
        $this->em->method('persist')->will($this->returnCallback(function ($entity) use (&$persistedEntity) {
            $persistedEntity = $entity;

            return true;
        }));

        $this->em->expects($this->once())->method('flush');

        // 4 events should be fired: loadPosters, loadTranslations + load similar movies + loadActors
        $this->producer->expects($this->exactly(4))->method('sendEvent');

        $this->movieSyncProcessor = new MovieSyncProcessor($this->em, $this->producer, $this->tmdbNormalizer, $this->logger, $this->repository, $this->dispatcher);
        $result = $this->movieSyncProcessor->process($this->psrMessage, $this->psrContext);

        $this->assertSame(123, $persistedEntity->getId());
        $this->assertSame($this->movieSyncProcessor::ACK, $result);
    }

    public function testThatAllMoviesWillBeSavedWithoutSimilar()
    {
        $movie = json_encode([
            'id' => 1,
            'original_title' => 'Test Title',
        ]);
        $this->psrMessage->method('getBody')->willReturn($movie);
        $this->psrMessage->method('getProperty')->with(MovieSyncProcessor::PARAM_LOAD_SIMILAR_MOVIES, false)->willReturn(false);

        $this->repository->method('findOneByIdOrTmdbId')->willReturn(null);

        $newMovie = $this->createMock(Movie::class);
        $newMovie->method('getId')->willReturn(123);
        $this->tmdbNormalizer->method('normalizeMoviesToObjects')->willReturn($this->getMoviesIterator($newMovie));

        $persistedEntity = null;
        $this->em->method('persist')->will($this->returnCallback(function ($entity) use (&$persistedEntity) {
            $persistedEntity = $entity;

            return true;
        }));

        $this->em->expects($this->once())->method('flush');

        // 3 events should be fired: loadPosters and loadTranslations + loadActors
        $this->producer->expects($this->exactly(3))->method('sendEvent');

        $this->movieSyncProcessor = new MovieSyncProcessor($this->em, $this->producer, $this->tmdbNormalizer, $this->logger, $this->repository, $this->dispatcher);
        $result = $this->movieSyncProcessor->process($this->psrMessage, $this->psrContext);

        $this->assertSame(123, $persistedEntity->getId());
        $this->assertSame($this->movieSyncProcessor::ACK, $result);
    }
}

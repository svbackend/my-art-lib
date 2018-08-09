<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventListener\Actor;

use App\Actors\EventListener\ActorSyncProcessor;
use App\Genres\Entity\Genre;
use App\Movies\DTO\MovieDTO;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTMDB;
use App\Movies\EventListener\MovieSyncProcessor;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbNormalizerService;
use App\Movies\Service\TmdbSearchService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ActorSyncProcessorTest extends KernelTestCase
{
    /** @var EntityManager|MockObject */
    private $em;

    /** @var PsrContext */
    private $psrContext;

    /** @var PsrMessage|MockObject */
    private $psrMessage;

    /** @var MockObject|ProducerInterface */
    private $producer;

    private $logger;

    /**
     * @var MockObject|MovieRepository
     */
    private $repository;

    /**
     * @var MockObject|TmdbSearchService
     */
    private $searchService;

    /**
     * @var ActorSyncProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->psrContext = $this->createMock(PsrContext::class);
        $this->psrMessage = $this->createMock(PsrMessage::class);
        $this->producer = $this->createMock(ProducerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(MovieRepository::class);
        $this->searchService = $this->createMock(TmdbSearchService::class);
        $this->processor = new ActorSyncProcessor($this->producer, $this->logger, $this->repository, $this->searchService);
    }

    public function testThatAllActorsWillBeSaved()
    {
        $movieId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($movieId);

        $movie = $this->createMock(Movie::class);
        $movie->method('getId')->willReturn(1);
        $movieTmdb = $this->createMock(MovieTMDB::class);
        $movieTmdb->method('getId')->willReturn(2);
        $movie->method('getTmdb')->willReturn($movieTmdb);

        $this->repository->method('find')->with(1)->willReturn($movie);

        $this->searchService->method('findActorsByMovieId')->with(2)->willReturn([
            'cast' => [
                ['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5], ['id' => 6], ['id' => 7], ['id' => 8], ['id' => 9], ['id' => 10], ['id' => 11],
            ]
        ]);

        // 2 events should be fired for each actor: saveActor + addActorToMovie
        $this->producer->expects($this->exactly(20))->method('sendEvent'); // 20 because we have 11 actors, but limit is 10, so 10*2 = 20

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertEquals($this->processor::ACK, $result);
    }

    public function testWhenMovieNotFound()
    {
        $movieId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($movieId);

        $this->repository->method('find')->with(1)->willReturn(null);

        $this->producer->expects($this->never())->method('sendEvent');

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertEquals($this->processor::REJECT, $result);
    }
}
<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventListener\Actor;

use App\Actors\Entity\Actor;
use App\Actors\Entity\ActorTMDB;
use App\Actors\EventListener\ActorAddToMovieProcessor;
use App\Actors\EventListener\ActorSyncProcessor;
use App\Actors\EventListener\SaveActorProcessor;
use App\Actors\Repository\ActorRepository;
use App\Genres\Entity\Genre;
use App\Movies\DTO\MovieDTO;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTMDB;
use App\Movies\EventListener\MovieSyncProcessor;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbNormalizerService;
use App\Movies\Service\TmdbSearchService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ActorAddToMovieProcessorTest extends KernelTestCase
{
    /** @var EntityManager|MockObject */
    private $em;

    /** @var PsrContext */
    private $psrContext;

    /** @var PsrMessage|MockObject */
    private $psrMessage;

    /** @var MockObject|ProducerInterface */
    private $producer;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var TmdbNormalizerService|MockObject
     */
    private $normalizer;

    /**
     * @var MockObject|ActorRepository
     */
    private $actorRepository;

    /**
     * @var MockObject|MovieRepository
     */
    private $movieRepository;

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
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->actorRepository = $this->createMock(ActorRepository::class);
        $this->movieRepository = $this->createMock(MovieRepository::class);

        $this->processor = new ActorAddToMovieProcessor($this->em, $this->logger, $this->movieRepository, $this->actorRepository);
    }

    public function testThatAllActorsWillBeAdded()
    {
        $messageData = json_encode([
            'movieId' => 1,
            'actorTmdbId' => 2,
        ]);
        $this->psrMessage->method('getBody')->willReturn($messageData);

        /** @var $addedActor Actor|null */
        $addedActor = null;
        $movie = $this->createMock(Movie::class);
        $movie->method('addActor')->willReturnCallback(function (Actor $actor) use (&$addedActor) {
            $addedActor = $actor;
        });
        $actor = $this->createMock(Actor::class);
        $actor->method('getId')->willReturn(10);

        $this->movieRepository->method('find')->with(1)->willReturn($movie);
        $this->actorRepository->method('findByTmdbId')->with(2)->willReturn($actor);

        $this->em->expects($this->exactly(1))->method('flush');
        $this->em->expects($this->exactly(1))->method('clear');

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertEquals($this->processor::ACK, $result);
        $this->assertEquals(10, $addedActor->getId());
    }

    public function testWhenMovieNotFound()
    {
        $messageData = json_encode([
            'movieId' => 1,
            'actorTmdbId' => 2,
        ]);
        $this->psrMessage->method('getBody')->willReturn($messageData);

        $this->movieRepository->method('find')->with(1)->willReturn(null);

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertEquals($this->processor::REJECT, $result);
    }

    public function testWhenActorNotFound()
    {
        $messageData = json_encode([
            'movieId' => 1,
            'actorTmdbId' => 2,
        ]);
        $this->psrMessage->method('getBody')->willReturn($messageData);
        $movie = $this->createMock(Movie::class);

        $this->movieRepository->method('find')->with(1)->willReturn($movie);
        $this->actorRepository->method('findByTmdbId')->with(2)->willReturn(null);

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertEquals($this->processor::REJECT, $result);
    }
}
<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener\Actor;

use App\Actors\Entity\Actor;
use App\Actors\EventListener\ActorSyncProcessor;
use App\Actors\EventListener\SaveActorProcessor;
use App\Actors\Repository\ActorRepository;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Service\TmdbNormalizerService;
use App\Movies\Service\TmdbSearchService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Interop\Queue\Context;
use Interop\Queue\Message;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SaveActorProcessorTest extends KernelTestCase
{
    /** @var EntityManager|MockObject */
    private $em;

    /** @var Context */
    private $psrContext;

    /** @var Message|MockObject */
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
        $this->psrContext = $this->createMock(Context::class);
        $this->psrMessage = $this->createMock(Message::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->producer = $this->createMock(ProducerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(ActorRepository::class);
        $this->searchService = $this->createMock(TmdbSearchService::class);
        $this->searchService = $this->createMock(TmdbSearchService::class);
        $this->normalizer = $this->createMock(TmdbNormalizerService::class);
        $this->processor = new SaveActorProcessor($this->em, $this->producer, $this->normalizer, $this->logger, $this->repository, $this->searchService);
    }

    private function getActorsIterator(Actor $actor): \Iterator
    {
        yield $actor;
    }

    public function testThatAllActorsWillBeSaved()
    {
        $actorTmdbId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorTmdbId);
        $this->repository->method('findByTmdbId')->with(1)->willReturn(null);

        $this->searchService->method('findActorById')->with(1)->willReturn([1]); // its actually doesn't matter in test
        $actor = $this->createMock(Actor::class);
        $this->normalizer->method('normalizeActorsToObjects')->willReturn($this->getActorsIterator($actor));

        $this->em->expects($this->exactly(1))->method('persist');
        $this->em->expects($this->exactly(1))->method('flush');
        $this->em->expects($this->exactly(1))->method('clear');

        $this->producer->expects($this->exactly(2))->method('sendEvent');

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->processor::ACK, $result);
    }

    public function testWhenActorAlreadySaved()
    {
        $actorTmdbId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorTmdbId);
        $actor = $this->createMock(Actor::class);
        $this->repository->method('findByTmdbId')->with(1)->willReturn($actor);

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->processor::REJECT, $result);
    }

    public function testThatTmdbLimitIsNotAProblem()
    {
        $actorTmdbId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorTmdbId);
        $this->repository->method('findByTmdbId')->with(1)->willReturn(null);
        $this->searchService->method('findActorById')->with(1)->willThrowException(new TmdbRequestLimitException());

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->processor::REQUEUE, $result);
    }

    public function testThatTmdbNotFoundIsNotAProblem()
    {
        $actorTmdbId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorTmdbId);
        $this->repository->method('findByTmdbId')->with(1)->willReturn(null);
        $this->searchService->method('findActorById')->with(1)->willThrowException(new TmdbMovieNotFoundException());

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->processor::REJECT, $result);
    }

    public function testThatUniqueConstraintIsNotAProblem()
    {
        $actorTmdbId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorTmdbId);
        $this->repository->method('findByTmdbId')->with(1)->willReturn(null);

        $this->searchService->method('findActorById')->with(1)->willReturn([1]); // its actually doesn't matter in test
        $actor = $this->createMock(Actor::class);
        $this->normalizer->method('normalizeActorsToObjects')->willReturn($this->getActorsIterator($actor));

        $this->em->expects($this->exactly(1))->method('persist');
        $this->em->expects($this->exactly(1))->method('flush');
        $this->em->expects($this->exactly(1))->method('clear');
        $exceptionMock = $this->createMock(UniqueConstraintViolationException::class);
        $this->em->method('flush')->willThrowException($exceptionMock);

        $this->producer->expects($this->never())->method('sendEvent');

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->processor::ACK, $result);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener\Actor;

use App\Actors\Entity\Actor;
use App\Actors\Entity\ActorTMDB;
use App\Actors\Entity\ActorTranslations;
use App\Actors\EventListener\ActorSyncProcessor;
use App\Actors\EventListener\ActorTranslationsProcessor;
use App\Actors\Repository\ActorRepository;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Service\TmdbNormalizerService;
use App\Movies\Service\TmdbSearchService;
use App\Service\LocaleService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Interop\Queue\Context;
use Interop\Queue\Message;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ActorTranslationsProcessorTest extends KernelTestCase
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

    private $locale;

    protected function setUp()
    {
        $this->psrContext = $this->createMock(Context::class);
        $this->psrMessage = $this->createMock(Message::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(ActorRepository::class);
        $this->searchService = $this->createMock(TmdbSearchService::class);
        $this->locale = $this->createMock(LocaleService::class);
        $this->locale->method('getLocales')->willReturn(['en', 'uk', 'ru', 'pl']);
        $this->processor = new ActorTranslationsProcessor($this->em, $this->logger, $this->repository, $this->searchService, $this->locale);
    }

    public function testThatAllActorTranslationsWillBeSaved()
    {
        $actorId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorId);

        $actorTranslation = $this->createMock(ActorTranslations::class);
        $actorTmdb = $this->createMock(ActorTMDB::class);
        $actorTmdb->method('getId')->willReturn(2);
        $actor = $this->createMock(Actor::class);
        $actor->method('getTmdb')->willReturn($actorTmdb);
        $actor->method('getId')->willReturn(1);
        $actor->method('getOriginalName')->willReturn('Original Name');
        $actor->method('getTranslation')->will($this->returnValueMap([
            ['en', false, $actorTranslation], // like only en translation is already exist
            ['uk', false, null],
            ['ru', false, null],
        ]));

        $this->repository->method('find')->with(1)->willReturn($actor);

        $this->searchService->method('findActorTranslationsById')->with(2)->willReturn([
            'translations' => [
                [
                    'iso_639_1' => 'en',
                    'data' => [
                        'biography' => 'en biography',
                    ],
                ],
                [
                    'iso_639_1' => 'uk',
                    'data' => [
                        'biography' => 'uk biography',
                    ],
                ],
                [
                    'iso_639_1' => 'ru',
                    'data' => [
                        'biography' => 'ru biography',
                    ],
                ],
            ],
        ]);

        $this->em->method('getReference')->willReturn($actor);

        $this->em->expects($this->exactly(2))->method('persist'); // for uk and ru translations, because en already saved
        $this->em->expects($this->exactly(1))->method('flush');
        $this->em->expects($this->exactly(1))->method('clear');

        /** @var $persistedEntities ActorTranslations[] */
        $persistedEntities = [];
        $this->em->method('persist')->willReturnCallback(function (ActorTranslations $entity) use (&$persistedEntities) {
            $persistedEntities[$entity->getLocale()] = $entity;
        });

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->processor::ACK, $result);
        $this->assertSame(2, count($persistedEntities));
        $this->assertContainsOnlyInstancesOf(ActorTranslations::class, $persistedEntities);
        $this->assertSame('uk biography', $persistedEntities['uk']->getBiography());
        $this->assertSame('ru biography', $persistedEntities['ru']->getBiography());
        $this->assertSame('Original Name', $persistedEntities['uk']->getName());
        $this->assertSame('Original Name', $persistedEntities['ru']->getName());
    }

    public function testWithNotFoundActor()
    {
        $actorId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorId);

        $this->repository->method('find')->with(1)->willReturn(null);

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->processor::REJECT, $result);
    }

    public function testThatTmdbRequestLimitIsNotAProblem()
    {
        $actorId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorId);

        $actorTmdb = $this->createMock(ActorTMDB::class);
        $actorTmdb->method('getId')->willReturn(2);
        $actor = $this->createMock(Actor::class);
        $actor->method('getTmdb')->willReturn($actorTmdb);

        $this->repository->method('find')->with(1)->willReturn($actor);

        $this->searchService->method('findActorTranslationsById')->willThrowException(new TmdbRequestLimitException());

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->processor::REQUEUE, $result);
    }

    public function testThatTmdbActorNotFoundIsNotAProblem()
    {
        $actorId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorId);

        $actorTmdb = $this->createMock(ActorTMDB::class);
        $actorTmdb->method('getId')->willReturn(2);
        $actor = $this->createMock(Actor::class);
        $actor->method('getTmdb')->willReturn($actorTmdb);

        $this->repository->method('find')->with(1)->willReturn($actor);

        $this->searchService->method('findActorTranslationsById')->willThrowException(new TmdbMovieNotFoundException());

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->processor::REJECT, $result);
    }

    public function testThatUniqueConstraintIsNotAProblem()
    {
        $actorId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorId);

        $actorTranslation = $this->createMock(ActorTranslations::class);
        $actorTmdb = $this->createMock(ActorTMDB::class);
        $actorTmdb->method('getId')->willReturn(2);
        $actor = $this->createMock(Actor::class);
        $actor->method('getTmdb')->willReturn($actorTmdb);
        $actor->method('getId')->willReturn(1);
        $actor->method('getOriginalName')->willReturn('Original Name');
        $actor->method('getTranslation')->will($this->returnValueMap([
            ['en', false, $actorTranslation], // like only en translation is already exist
            ['uk', false, null],
        ]));

        $this->repository->method('find')->with(1)->willReturn($actor);

        $this->searchService->method('findActorTranslationsById')->with(2)->willReturn([
            'translations' => [
                [
                    'iso_639_1' => 'en',
                    'data' => [
                        'biography' => 'en biography',
                    ],
                ],
                [
                    'iso_639_1' => 'uk',
                    'data' => [
                        'biography' => 'uk biography',
                    ],
                ],
            ],
        ]);

        $this->em->method('getReference')->willReturn($actor);

        $this->em->expects($this->exactly(1))->method('persist');
        $this->em->expects($this->exactly(1))->method('flush');
        $this->em->expects($this->exactly(1))->method('clear');

        $exception = $this->createMock(UniqueConstraintViolationException::class);
        $this->em->method('flush')->willThrowException($exception);

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->processor::ACK, $result);
    }
}

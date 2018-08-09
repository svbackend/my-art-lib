<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventListener\Actor;

use App\Actors\Entity\Actor;
use App\Actors\Entity\ActorTMDB;
use App\Actors\Entity\ActorTranslations;
use App\Actors\EventListener\ActorSyncProcessor;
use App\Actors\EventListener\ActorTranslationsProcessor;
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
use App\Service\LocaleService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ActorTranslationsProcessorTest extends KernelTestCase
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
        $this->psrContext = $this->createMock(PsrContext::class);
        $this->psrMessage = $this->createMock(PsrMessage::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->producer = $this->createMock(ProducerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(ActorRepository::class);
        $this->searchService = $this->createMock(TmdbSearchService::class);
        $this->searchService = $this->createMock(TmdbSearchService::class);
        $this->normalizer = $this->createMock(TmdbNormalizerService::class);
        $this->locale = $this->createMock(LocaleService::class);
        $this->locale->method('getLocales')->willReturn(['en', 'uk', 'ru', 'pl']);
        $this->processor = new ActorTranslationsProcessor($this->em, $this->logger, $this->repository, $this->searchService, $this->locale);
    }

    private function getActorsIterator(Actor $actor): \Iterator
    {
        yield $actor;
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
                        'biography' => 'en biography'
                    ],
                ],
                [
                    'iso_639_1' => 'uk',
                    'data' => [
                        'biography' => 'uk biography'
                    ],
                ],
                [
                    'iso_639_1' => 'ru',
                    'data' => [
                        'biography' => 'ru biography'
                    ],
                ],
            ]
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

        $this->assertEquals($this->processor::ACK, $result);
        $this->assertEquals(2, count($persistedEntities));
        $this->assertContainsOnlyInstancesOf(ActorTranslations::class, $persistedEntities);
        $this->assertEquals('uk biography', $persistedEntities['uk']->getBiography());
        $this->assertEquals('ru biography', $persistedEntities['ru']->getBiography());
        $this->assertEquals('Original Name', $persistedEntities['uk']->getName());
        $this->assertEquals('Original Name', $persistedEntities['ru']->getName());
    }

    public function testWithNotFoundActor()
    {
        $actorId = json_encode(1);
        $this->psrMessage->method('getBody')->willReturn($actorId);

        $this->repository->method('find')->with(1)->willReturn(null);

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertEquals($this->processor::REJECT, $result);
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

        $this->assertEquals($this->processor::REQUEUE, $result);
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

        $this->assertEquals($this->processor::REJECT, $result);
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
                        'biography' => 'en biography'
                    ],
                ],
                [
                    'iso_639_1' => 'uk',
                    'data' => [
                        'biography' => 'uk biography'
                    ],
                ]
            ]
        ]);

        $this->em->method('getReference')->willReturn($actor);

        $this->em->expects($this->exactly(1))->method('persist');
        $this->em->expects($this->exactly(1))->method('flush');
        $this->em->expects($this->exactly(1))->method('clear');

        $exception = $this->createMock(UniqueConstraintViolationException::class);
        $this->em->method('flush')->willThrowException($exception);

        $result = $this->processor->process($this->psrMessage, $this->psrContext);

        $this->assertEquals($this->processor::ACK, $result);
    }
}
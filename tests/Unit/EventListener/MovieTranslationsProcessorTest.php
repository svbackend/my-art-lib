<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTMDB;
use App\Movies\Entity\MovieTranslations;
use App\Movies\EventListener\MovieTranslationsProcessor;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbSearchService;
use App\Service\LocaleService;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MovieTranslationsProcessorTest extends KernelTestCase
{
    /** @var EntityManagerInterface|MockObject */
    private $em;

    /** @var PsrContext */
    private $psrContext;

    /** @var PsrMessage|MockObject */
    private $psrMessage;

    /** @var MovieTranslationsProcessor */
    private $movieTranslationsProcessor;

    /** @var MovieRepository|MockObject */
    private $movieRepository;

    /** @var TmdbSearchService|MockObject */
    private $searchService;

    /** @var LocaleService|MockObject */
    private $localeService;

    /** @var ProducerInterface|MockObject */
    private $producer;

    /**
     * @throws \ReflectionException
     */
    public function setUp()
    {
        $this->psrContext = $this->createMock(PsrContext::class);
        $this->psrMessage = $this->createMock(PsrMessage::class);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->movieRepository = $this->createMock(MovieRepository::class);
        $this->searchService = $this->createMock(TmdbSearchService::class);
        $this->localeService = $this->createMock(LocaleService::class);
        $this->localeService->method('getLocales')->willReturn(['en', 'uk', 'ru']);
        $this->producer = $this->createMock(ProducerInterface::class);

        $this->movieTranslationsProcessor = new MovieTranslationsProcessor($this->em, $this->producer, $this->movieRepository, $this->searchService, $this->localeService);
    }

    public function testThatAllMoviesTranslationsWillBeCorrectlySaved()
    {
        $movieId = 1;
        $this->psrMessage->method('getBody')->willReturn(json_encode($movieId));

        $movieTMDB = $this->createMock(MovieTMDB::class);
        $movieTMDB->method('getId')->willReturn(2);

        $movie1 = $this->createMock(Movie::class);
        $movie1->method('getTranslation')->withAnyParameters()->willReturn(null);
        $movie1->method('getTmdb')->willReturn($movieTMDB);

        $this->movieRepository->method('find')->with($movieId)->willReturn($movie1);

        $this->em->method('getReference')->willReturn($movie1);

        $tmdbResponse = [
            'translations' => [
                ['iso_639_1' => 'en', 'data' => ['title' => 'Title (en)', 'overview' => 'Overview (en)']],
                ['iso_639_1' => 'ru', 'data' => ['title' => 'Title (ru)', 'overview' => 'Overview (ru)']],
                ['iso_639_1' => 'uk', 'data' => ['title' => 'Title (uk)', 'overview' => 'Overview (uk)']],
            ],
        ];
        $this->searchService->method('findMovieTranslationsById')->willReturn($tmdbResponse);

        $persistedEntities = [];
        $this->em->method('persist')->will($this->returnCallback(function ($entity) use (&$persistedEntities) {
            $persistedEntities[] = $entity;

            return true;
        }));

        $this->em->expects($this->once())->method('flush');

        $result = $this->movieTranslationsProcessor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->movieTranslationsProcessor::ACK, $result);
        $this->assertSame(3, count($persistedEntities)); // 3 locales
        $this->assertContainsOnlyInstancesOf(MovieTranslations::class, $persistedEntities);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \ErrorException
     * @expectedException \App\Movies\Exception\TmdbMovieNotFoundException
     */
    public function testThatTmdbNotFoundIsntAProblem()
    {
        $movieId = 1;
        $this->psrMessage->method('getBody')->willReturn(json_encode($movieId));

        $movieTMDB = $this->createMock(MovieTMDB::class);
        $movieTMDB->method('getId')->willReturn(2);

        $movie1 = $this->createMock(Movie::class);
        $movie1->method('getTranslation')->withAnyParameters()->willReturn(null);
        $movie1->method('getTmdb')->willReturn($movieTMDB);

        $this->movieRepository->method('find')->with($movieId)->willReturn($movie1);

        $this->em->method('getReference')->willReturn($movie1);

        $this->searchService->method('findMovieTranslationsById')->willThrowException(new \App\Movies\Exception\TmdbMovieNotFoundException());

        $persistedEntities = [];
        $this->em->method('persist')->will($this->returnCallback(function ($entity) use (&$persistedEntities) {
            $persistedEntities[] = $entity;

            return true;
        }));

        $this->em->expects($this->never())->method('flush');

        $result = $this->movieTranslationsProcessor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->movieTranslationsProcessor::REJECT, $result);
        $this->assertSame(0, count($persistedEntities));
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \ErrorException
     * @expectedException \App\Movies\Exception\TmdbRequestLimitException
     */
    public function testThatTmdbRequestLimitIsntAProblem()
    {
        $movieId = 1;
        $this->psrMessage->method('getBody')->willReturn(json_encode($movieId));

        $movieTMDB = $this->createMock(MovieTMDB::class);
        $movieTMDB->method('getId')->willReturn(2);

        $movie1 = $this->createMock(Movie::class);
        $movie1->method('getTranslation')->withAnyParameters()->willReturn(null);
        $movie1->method('getTmdb')->willReturn($movieTMDB);

        $this->movieRepository->method('find')->with($movieId)->willReturn($movie1);

        $this->em->method('getReference')->willReturn($movie1);

        $this->searchService->method('findMovieTranslationsById')->willThrowException(new \App\Movies\Exception\TmdbRequestLimitException());

        $persistedEntities = [];
        $this->em->method('persist')->will($this->returnCallback(function ($entity) use (&$persistedEntities) {
            $persistedEntities[] = $entity;

            return true;
        }));

        $this->em->expects($this->never())->method('flush');

        $result = $this->movieTranslationsProcessor->process($this->psrMessage, $this->psrContext);

        $this->assertSame($this->movieTranslationsProcessor::REQUEUE, $result);
        $this->assertSame(0, count($persistedEntities));
    }
}

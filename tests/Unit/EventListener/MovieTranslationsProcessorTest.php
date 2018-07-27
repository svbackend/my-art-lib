<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTMDB;
use App\Movies\Entity\MovieTranslations;
use App\Movies\EventListener\MovieTranslationsProcessor;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbSearchService;
use App\Service\LocaleService;
use Doctrine\ORM\EntityManager;
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

        $this->em = $this->createMock(EntityManager::class);
        $this->movieRepository = $this->createMock(MovieRepository::class);
        $this->searchService = $this->createMock(TmdbSearchService::class);
        $this->localeService = $this->createMock(LocaleService::class);
        $this->localeService->method('getLocales')->willReturn(['en', 'uk', 'ru']);
        $this->producer = $this->createMock(ProducerInterface::class);

        $this->movieTranslationsProcessor = new MovieTranslationsProcessor($this->em, $this->producer, $this->movieRepository, $this->searchService, $this->localeService);
    }

    /**
     * @throws \ReflectionException
     * @expectedException \InvalidArgumentException
     */
    public function testConfigurationException()
    {
        /** @var $emInterfaceMock EntityManagerInterface */
        $emInterfaceMock = $this->createMock(EntityManagerInterface::class);
        $this->movieTranslationsProcessor = new MovieTranslationsProcessor($emInterfaceMock, $this->producer, $this->movieRepository, $this->searchService, $this->localeService);
    }

    public function testThatAllMoviesTranslationsWillBeCorrectlySaved()
    {
        $moviesIdsArray = [1, 2, 3];
        $moviesIds = serialize($moviesIdsArray);
        $this->psrMessage->method('getBody')->willReturn($moviesIds);

        $this->em->method('isOpen')->willReturn(true);

        $movieTMDB = $this->createMock(MovieTMDB::class);
        $movieTMDB->method('getId')->willReturn(0);

        $movie1 = $this->createMock(Movie::class);
        $movie1->method('getTranslation')->withAnyParameters()->willReturn(null);
        $movie1->method('getTmdb')->willReturn($movieTMDB);

        $movie2 = $this->createMock(Movie::class);
        $movie2->method('getTranslation')->withAnyParameters()->willReturn(null);
        $movie2->method('getTmdb')->willReturn($movieTMDB);

        $movie3 = $this->createMock(Movie::class);
        $movie3->method('getTranslation')->withAnyParameters()->willReturn(null);
        $movie3->method('getTmdb')->willReturn($movieTMDB);

        $this->em->method('getReference')->willReturn($movie1, $movie2, $movie3);

        $movies = [$movie1, $movie2, $movie3];
        $this->movieRepository->method('findAllByIds')->with($moviesIdsArray)->willReturn($movies);

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

        $this->assertEquals($this->movieTranslationsProcessor::ACK, $result);
        $this->assertEquals(9, count($persistedEntities)); // 3 movies * 3 locales
        $this->assertContainsOnlyInstancesOf(MovieTranslations::class, $persistedEntities);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \ErrorException
     * @throws \ReflectionException
     */
    public function testThatTmdbRequestLimitIsNotAProblem()
    {
        $moviesIdsArray = [1];
        $moviesIds = serialize($moviesIdsArray);
        $this->psrMessage->method('getBody')->willReturn($moviesIds);

        $this->em->method('isOpen')->willReturn(true);

        $movieTMDB = $this->createMock(MovieTMDB::class);
        $movieTMDB->method('getId')->willReturn(0);

        $movie1 = $this->createMock(Movie::class);
        $movie1->method('getId')->willReturn(1);
        $movie1->method('getTranslation')->withAnyParameters()->willReturn(null);
        $movie1->method('getTmdb')->willReturn($movieTMDB);
        $this->em->method('getReference')->willReturn($movie1);

        $movies = [$movie1];
        $this->movieRepository->method('findAllByIds')->with($moviesIdsArray)->willReturn($movies);

        $this->searchService->method('findMovieTranslationsById')->willThrowException(new TmdbRequestLimitException());

        $persistedEntities = [];
        $this->em->method('persist')->will($this->returnCallback(function ($entity) use (&$persistedEntities) {
            $persistedEntities[] = $entity;
            return true;
        }));

        $this->em->expects($this->once())->method('flush');
        $this->producer->expects($this->once())->method('sendEvent');

        $result = $this->movieTranslationsProcessor->process($this->psrMessage, $this->psrContext);

        $this->assertEquals($this->movieTranslationsProcessor::ACK, $result);
    }

    public function testThatTmdbMovieNotFoundIsNotAProblem()
    {
        $moviesIdsArray = [1];
        $moviesIds = serialize($moviesIdsArray);
        $this->psrMessage->method('getProperty')->with('retry', true)->willReturn(false);
        $this->psrMessage->method('getBody')->willReturn($moviesIds);

        $this->em->method('isOpen')->willReturn(true);

        $movieTMDB = $this->createMock(MovieTMDB::class);
        $movieTMDB->method('getId')->willReturn(0);

        $movie1 = $this->createMock(Movie::class);
        $movie1->method('getId')->willReturn(1);
        $movie1->method('getTranslation')->withAnyParameters()->willReturn(null);
        $movie1->method('getTmdb')->willReturn($movieTMDB);
        $this->em->method('getReference')->willReturn($movie1);

        $movies = [$movie1];
        $this->movieRepository->method('findAllByIds')->with($moviesIdsArray)->willReturn($movies);

        $this->searchService->method('findMovieTranslationsById')->willThrowException(new TmdbMovieNotFoundException());

        $persistedEntities = [];
        $this->em->method('persist')->will($this->returnCallback(function ($entity) use (&$persistedEntities) {
            $persistedEntities[] = $entity;
            return true;
        }));

        $this->em->expects($this->once())->method('flush');

        $result = $this->movieTranslationsProcessor->process($this->psrMessage, $this->psrContext);

        $this->assertEquals($this->movieTranslationsProcessor::ACK, $result);
        $this->assertEquals(0, count($persistedEntities));
    }
}
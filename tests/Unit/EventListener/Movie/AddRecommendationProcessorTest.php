<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener\Movie;

use App\Movies\Entity\Movie;
use App\Movies\EventListener\AddRecommendationProcessor;
use App\Movies\Repository\MovieRepository;
use App\Users\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AddRecommendationProcessorTest extends KernelTestCase
{
    /** @var EntityManager|MockObject */
    private $em;

    /** @var PsrContext */
    private $psrContext;

    /** @var PsrMessage|MockObject */
    private $psrMessage;

    /**
     * @var MockObject|MovieRepository
     */
    private $repository;

    /**
     * @var AddRecommendationProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->psrContext = $this->createMock(PsrContext::class);
        $this->psrMessage = $this->createMock(PsrMessage::class);
        $this->repository = $this->createMock(MovieRepository::class);

        $this->processor = new AddRecommendationProcessor($this->em, $this->repository);
    }

    public function testAddRecommendationSuccess()
    {
        $this->psrMessage->method('getBody')->willReturn(json_encode([
            'movie_id' => 1,
            'tmdb_id' => 2,
            'user_id' => 3,
        ]));

        $originalMovie = $this->createMock(Movie::class);
        $recommendedMovie = $this->createMock(Movie::class);

        $this->repository->method('findOneByIdOrTmdbId')->willReturnMap([
            [1, null, $originalMovie],
            [null, 2, $recommendedMovie],
        ]);

        $originalMovie->expects($this->once())->method('addRecommendation');

        $user = $this->createMock(User::class);
        $this->em->method('getReference')->with(User::class, 3)->willReturn($user);

        $this->em->expects($this->once())->method('persist')->with($originalMovie);
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('clear');

        $result = $this->processor->process($this->psrMessage, $this->psrContext);
        $this->assertEquals($this->processor::ACK, $result);
    }

    public function testAddRecommendationWithNotFoundOriginalMovie()
    {
        $this->psrMessage->method('getBody')->willReturn(json_encode([
            'movie_id' => 1,
            'tmdb_id' => 2,
            'user_id' => 3,
        ]));

        $recommendedMovie = $this->createMock(Movie::class);

        $this->repository->method('findOneByIdOrTmdbId')->willReturnMap([
            [1, null, null],
            [null, 2, $recommendedMovie],
        ]);

        $result = $this->processor->process($this->psrMessage, $this->psrContext);
        $this->assertEquals($this->processor::REJECT, $result);
    }

    public function testAddRecommendationWithNotFoundRecommendedMovie()
    {
        $this->psrMessage->method('getBody')->willReturn(json_encode([
            'movie_id' => 1,
            'tmdb_id' => 2,
            'user_id' => 3,
        ]));

        $originalMovie = $this->createMock(Movie::class);

        $this->repository->method('findOneByIdOrTmdbId')->willReturnMap([
            [1, null, $originalMovie],
            [null, 2, null],
        ]);

        $result = $this->processor->process($this->psrMessage, $this->psrContext);
        $this->assertEquals($this->processor::REJECT, $result);
    }

    public function testAddRecommendationWithUniqueConstraint()
    {
        $this->psrMessage->method('getBody')->willReturn(json_encode([
            'movie_id' => 1,
            'tmdb_id' => 2,
            'user_id' => 3,
        ]));

        $originalMovie = $this->createMock(Movie::class);
        $recommendedMovie = $this->createMock(Movie::class);

        $this->repository->method('findOneByIdOrTmdbId')->willReturnMap([
            [1, null, $originalMovie],
            [null, 2, $recommendedMovie],
        ]);

        $originalMovie->expects($this->once())->method('addRecommendation');

        $user = $this->createMock(User::class);
        $this->em->method('getReference')->with(User::class, 3)->willReturn($user);

        $this->em->expects($this->once())->method('persist')->with($originalMovie);
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('clear');

        $exception = $this->createMock(UniqueConstraintViolationException::class);
        $this->em->method('flush')->willThrowException($exception);

        $result = $this->processor->process($this->psrMessage, $this->psrContext);
        $this->assertEquals($this->processor::ACK, $result);
    }
}

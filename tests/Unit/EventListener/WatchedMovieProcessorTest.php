<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Genres\Entity\Genre;
use App\Movies\Entity\Movie;
use App\Movies\EventListener\WatchedMovieProcessor;
use App\Users\Entity\User;
use App\Users\Entity\UserWatchedMovie;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WatchedMovieProcessorTest extends KernelTestCase
{
    /** @var EntityManagerInterface|MockObject */
    private $em;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var PsrContext */
    private $psrContext;

    /** @var PsrMessage|MockObject */
    private $psrMessage;

    /** @var WatchedMovieProcessor */
    private $watchedMovieProcessor;

    /**
     * @throws \ReflectionException
     */
    public function setUp()
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->psrContext = $this->createMock(PsrContext::class);
        $this->psrMessage = $this->createMock(PsrMessage::class);
        $this->watchedMovieProcessor = new WatchedMovieProcessor($this->em, $this->logger);
    }

    /**
     * @throws \ReflectionException|\Exception
     */
    public function testThatAllUserMoviesWillBeCorrectlySaved()
    {
        /** @var $user User|MockObject */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        /** @var $genre Genre|MockObject */
        $genre = $this->createMock(Genre::class);
        $genre->method('getId')->willReturn(2);

        /** @var $movie Movie|MockObject */
        $movie = $this->createMock(Movie::class);
        $movie->method('getGenres')->willReturn([$genre]);

        $watchedAt = new \DateTimeImmutable();
        $userWatchedMovie1 = new UserWatchedMovie($user, $movie, 5.5, $watchedAt);
        $movies = serialize([$userWatchedMovie1]);

        $this->em->method('getReference')->will($this->returnValueMap([
            [Genre::class, $genre->getId(), $genre],
            [User::class, $user->getId(), $user],
        ]));
        $this->psrMessage->method('getBody')->willReturn($movies);

        $persistedEntities = [];
        $this->em->method('persist')->will($this->returnCallback(function ($entity) use (&$persistedEntities) {
            $persistedEntities[] = $entity;
            return true;
        }));

        $this->em->expects($this->once())->method('flush');
        $this->watchedMovieProcessor->process($this->psrMessage, $this->psrContext);

        $persistedEntitiesCount = count($persistedEntities);
        self::assertEquals(2, $persistedEntitiesCount); // UserWatchedMovie & Movie

        $incorrectEntities = array_filter($persistedEntities, function ($entity) {
            // We should persist only Movie and UserWatchedMovie so any other entities are incorrect
            return $entity instanceof UserWatchedMovie === false && $entity instanceof Movie === false;
        });

        if (count($incorrectEntities) > 0) {
            $this->fail('Some of your entities are persisted instead of just be associated through reference');
        }

        /** @var $newUserWatchedMovie UserWatchedMovie */
        $newUserWatchedMovieArray = array_filter($persistedEntities, function ($entity) {
            return $entity instanceof UserWatchedMovie;
        });
        $newUserWatchedMovie = reset($newUserWatchedMovieArray);
        self::assertEquals(1, $newUserWatchedMovie->getUser()->getId());
        self::assertEquals(5.5, $newUserWatchedMovie->getVote());
        self::assertEquals($watchedAt->getTimestamp(), $newUserWatchedMovie->getWatchedAt()->getTimestamp());

        /** @var $newMovie Movie */
        $movieArray = array_filter($persistedEntities, function ($entity) {
            return $entity instanceof Movie;
        });
        $newMovie = reset($movieArray);
        $genresReferences = $newMovie->getGenres();
        $genreReference = reset($genresReferences);

        self::assertEquals($genre->getId(), $genreReference->getId());
        self::assertEquals(5.5, $newUserWatchedMovie->getVote());
        self::assertEquals($watchedAt->getTimestamp(), $newUserWatchedMovie->getWatchedAt()->getTimestamp());
    }
}
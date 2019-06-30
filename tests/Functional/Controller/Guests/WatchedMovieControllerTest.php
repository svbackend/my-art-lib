<?php

namespace App\Tests\Functional\Controller\Guests;

use App\Guests\DataFixtures\GuestsFixtures;
use App\Guests\Entity\GuestWatchedMovie;
use App\Movies\DataFixtures\MoviesFixtures;
use App\Movies\EventListener\WatchedMovieProcessor;
use Enqueue\Client\ProducerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WatchedMovieControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;
    protected static $movies;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public static function getMovies()
    {
        if (self::$movies) {
            return self::$movies;
        }

        $client = self::$client;

        $client->request('GET', '/api/movies');
        self::$movies = json_decode($client->getResponse()->getContent(), true)['data'];

        return self::$movies;
    }

    public function testAddWatchedMovieWithTmdbId()
    {
        $client = self::$client;
        $guestSessionToken = GuestsFixtures::GUEST_SESSION_TOKEN;

        $client->request('POST', "/api/guests/{$guestSessionToken}/watchedMovies", [
            'movie' => [
                'id' => null,
                'tmdbId' => (int) MoviesFixtures::MOVIE_TMDB_ID,
                'vote' => null,
                'watchedAt' => null,
            ],
        ]);

        $this->assertSame(202, $client->getResponse()->getStatusCode());
    }

    public function testAddWatchedMovieWithTmdbIdWhichIsNotSavedYet()
    {
        $client = self::$client;
        $guestSessionToken = GuestsFixtures::GUEST_SESSION_TOKEN;
        $tmdbId = 141; // Movie which is not saved* in our database (*in test database of course)

        $client->request('POST', "/api/guests/{$guestSessionToken}/watchedMovies", [
            'movie' => [
                'id' => null,
                'tmdbId' => $tmdbId,
                'vote' => null,
                'watchedAt' => null,
            ],
        ]);

        $this->assertSame(202, $client->getResponse()->getStatusCode());

        // Test that movie will be saved in background
        $traces = $this->getProducer($client)->getTopicTraces(WatchedMovieProcessor::ADD_WATCHED_MOVIE_TMDB);
        $this->assertCount(1, $traces);
        $movies = unserialize($traces[0]['body']);
        $movie = reset($movies);
        /* @var $movie GuestWatchedMovie */
        self::assertInstanceOf(GuestWatchedMovie::class, $movie);
        self::assertSame($tmdbId, $movie->getMovie()->getTmdb()->getId());
        self::assertSame($guestSessionToken, $movie->getGuestSession()->getToken());
    }

    public function testAddWatchedMovieWithId()
    {
        $client = self::$client;
        $movies = self::getMovies();
        $movie = reset($movies);
        $guestSessionToken = GuestsFixtures::GUEST_SESSION_TOKEN;

        $client->request('POST', "/api/guests/{$guestSessionToken}/watchedMovies", [
            'movie' => [
                'id' => (int) $movie['id'],
                'tmdbId' => null,
                'vote' => null,
                'watchedAt' => null,
            ],
        ]);

        $this->assertSame(202, $client->getResponse()->getStatusCode());
    }

    /**
     * @param $client
     *
     * @return ProducerInterface
     */
    private function getProducer($client)
    {
        return $client->getContainer()->get('enqueue.client.default.producer');
    }
}

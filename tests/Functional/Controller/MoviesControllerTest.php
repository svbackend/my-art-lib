<?php

namespace App\Tests\Functional\Controller;

use App\Movies\DataFixtures\MoviesFixtures;
use App\Movies\Entity\Movie;
use App\Movies\Event\MovieSyncProcessor;
use App\Users\DataFixtures\UsersFixtures;
use Enqueue\Client\TraceableProducer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MoviesControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    private static $genresIds;

    private static function getGenresIds()
    {
        if (self::$genresIds) {
            return self::$genresIds;
        }

        $client = self::$client;

        $client->request('GET', "/api/genres");
        $genres = json_decode($client->getResponse()->getContent(), true);
        $genresIds = [];
        foreach ($genres as $genre) {
            $genresIds[] = ['id' => $genre['id']];
        }

        self::$genresIds = $genresIds;
        return $genresIds;
    }

    public function testGetAll()
    {
        $client = self::$client;
        $client->request('get', '/api/movies');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testCreateMovieWithInvalidData()
    {
        $client = self::$client;

        $client->request('POST', "/api/movies", [
            'movie' => [
                #'originalTitle' => 'Original Title',
                'originalTitle' => null,
                'originalPosterUrl' => '//placehold.it/480x340',
                'imdbId' => 'qwe', // not valid
                'runtime' => 120,
                'budget' => null, // valid
                'releaseDate' => '2011-01-20', // yyyy-mm-dd
                'translations' => [
                    [
                        'title' => 'TestMovieTitle',
                        'locale' => 'en',
                        'posterUrl' => '//placehold.it/480x320',
                        'overview' => 'Overview (en)',
                    ],
                    [
                        'title' => 'TestMovieTitle (Invalid)',
                        'locale' => 'INVALID_LOCALE',
                        'posterUrl' => '//placehold.it/480x320',
                        'overview' => 'Overview (INVALID_LOCALE)',
                    ],
                ],
                'tmdb' => [
                    'id' => 1,
                    'voteAverage' => '7.88',
                    'voteCount' => 500,
                ],
                'genres' => self::getGenresIds(),
            ]
        ]);

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('message', $response);
        self::assertArrayHasKey('errors', $response);
    }

    public function testCreateMovieWithoutPermissions()
    {
        $client = self::$client;

        $client->request('POST', "/api/movies", [
            'movie' => [
                'originalTitle' => 'Original Title',
                'originalPosterUrl' => '//placehold.it/480x340',
                'imdbId' => 'qwerty-123',
                'runtime' => 120,
                'budget' => 600000,
                'releaseDate' => '2011-01-20',
                'translations' => [
                    [
                        'title' => 'TestMovieTitle (en)',
                        'locale' => 'en',
                        'posterUrl' => '//placehold.it/480x320',
                        'overview' => '(en) Overview lorem ipsum sit doler amet considolous et alte',
                    ],
                    [
                        'title' => 'TestMovieTitle (uk)',
                        'locale' => 'uk',
                        'posterUrl' => '//placehold.it/480x320',
                        'overview' => '(uk) Overview lorem ipsum sit doler amet considolous et alte',
                    ],
                ],
                'tmdb' => [
                    'id' => 1,
                    'voteAverage' => 7.88,
                    'voteCount' => 500,
                ],
                'genres' => self::getGenresIds(),
            ]
        ]);

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testCreateMovieSuccess()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::ADMIN_API_TOKEN;

        $client->request('POST', "/api/movies?api_token={$apiToken}", [
            'movie' => [
                'originalTitle' => 'Original Title',
                'originalPosterUrl' => '//placehold.it/480x340',
                'imdbId' => 'qwerty-123',
                'runtime' => 120,
                'budget' => 600000,
                'releaseDate' => '2011-01-20',
                'translations' => [
                    [
                        'title' => 'TestMovieTitle (en)',
                        'locale' => 'en',
                        'posterUrl' => '//placehold.it/480x320',
                        'overview' => '(en) Overview lorem ipsum sit doler amet considolous et alte',
                    ],
                    [
                        'title' => 'TestMovieTitle (uk)',
                        'locale' => 'uk',
                        'posterUrl' => '//placehold.it/480x320',
                        'overview' => '(uk) Overview lorem ipsum sit doler amet considolous et alte',
                    ],
                ],
                'tmdb' => [
                    'id' => 1,
                    'voteAverage' => 7.88,
                    'voteCount' => 500,
                ],
                'genres' => self::getGenresIds(),
            ]
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        // because entity should be already translated and merged with translation based on user preferred locale:
        self::assertArrayNotHasKey('translations', $response);

        self::assertArrayHasKey('genres', $response);
        self::assertArrayHasKey('releaseDate', $response);
        self::assertArrayHasKey('budget', $response);
        self::assertArrayHasKey('imdbId', $response);
        self::assertArrayHasKey('originalPosterUrl', $response);
        self::assertArrayHasKey('originalTitle', $response);
    }

    public function testFindExistingMovieInOurDatabase()
    {
        $this->checkIsApiKeyProvided();

        $client = self::$client;
        $client->request('POST', "/api/movies/search", [
            'query' => MoviesFixtures::MOVIE_TITLE, // Title of created movie
        ]);

        self::assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue(count($response) > 0);
        $movie = reset($response);
        self::assertArrayHasKey('id', $movie);
        self::assertArrayHasKey('originalTitle', $movie);
        self::assertArrayHasKey('originalPosterUrl', $movie);
        self::assertArrayHasKey('locale', $movie);
        self::assertNotEmpty($movie['id']);
    }

    public function testFindMovieInTMDB()
    {
        $this->checkIsApiKeyProvided();

        $movieTitle = 'The 15:17 to Paris'; // Name of movie https://www.themoviedb.org/movie/453201-the-15-17-to-paris
        $client = self::$client;
        $client->request('POST', "/api/movies/search?language=ru", [
            'query' => $movieTitle,
        ]);

        self::assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue(count($response) > 0);
        $movie = reset($response);
        self::assertArrayHasKey('id', $movie);
        self::assertArrayHasKey('originalTitle', $movie);
        self::assertArrayHasKey('originalPosterUrl', $movie);
        self::assertArrayHasKey('locale', $movie);

        self::assertArrayHasKey('tmdb', $movie);
        self::assertArrayHasKey('id', $movie['tmdb']);
        self::assertNotEmpty($movie['tmdb']['id']); // use this id to open movie details page

        self::assertEmpty($movie['id']); // because movie should not be saved instantly
        self::assertEquals('ru', $movie['locale']);
        self::assertEquals($movieTitle, $movie['originalTitle']);

        // Test that movies will be saved in background
        $traces = $this->getProducer($client)->getTopicTraces(MovieSyncProcessor::ADD_MOVIES_TMDB);
        $this->assertCount(1, $traces);
        $movies = unserialize($traces[0]['body']);
        $movie = reset($movies);
        self::assertInstanceOf(Movie::class, $movie);
    }

    /**
     * @param $client
     * @return TraceableProducer
     */
    private function getProducer($client)
    {
        return $client->getContainer()->get(TraceableProducer::class);
    }

    private function checkIsApiKeyProvided()
    {
        if (!\getenv('MOVIE_DB_API_KEY')) {
            $this->fail('You should provide MOVIE_DB_API_KEY in your .env.test');
        }
    }
}
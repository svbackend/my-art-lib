<?php

namespace App\Tests\Functional\Controller\Movies;

use App\Genres\DataFixtures\GenresFixtures;
use App\Movies\DataFixtures\MoviesFixtures;
use App\Movies\Entity\Movie;
use App\Movies\EventListener\MovieSyncProcessor;
use App\Movies\Utils\Poster;
use App\Users\DataFixtures\UsersFixtures;
use Enqueue\Client\ProducerInterface;
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

        $client->request('GET', '/api/genres');
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
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testCreateMovieWithInvalidData()
    {
        $client = self::$client;

        $client->request('POST', '/api/movies', [
            'movie' => [
                //'originalTitle' => 'Original Title',
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
            ],
        ]);

        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('message', $response);
        self::assertArrayHasKey('errors', $response);
    }

    public function testCreateMovieWithoutPermissions()
    {
        $client = self::$client;

        $client->request('POST', '/api/movies', [
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
            ],
        ]);

        $this->assertSame(401, $client->getResponse()->getStatusCode());
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
                    'id' => 141,
                    'voteAverage' => 7.88,
                    'voteCount' => 500,
                ],
                'genres' => self::getGenresIds(),
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        // because entity should be already translated and merged with translation based on user preferred locale:
        self::assertArrayNotHasKey('translations', $response);

        self::assertArrayHasKey('id', $response);
        self::assertArrayHasKey('genres', $response);
        self::assertArrayHasKey('releaseDate', $response);
        self::assertArrayHasKey('budget', $response);
        self::assertArrayHasKey('imdbId', $response);
        self::assertArrayHasKey('originalPosterUrl', $response);
        self::assertArrayHasKey('originalTitle', $response);
    }

    public function testAddMovieRecommendation()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;

        $client->request('GET', '/api/movies');
        $movies = json_decode($client->getResponse()->getContent(), true)['data'];

        $originalMovie = $movies[0];
        $recommendedMovie = $movies[1];

        $client->request('POST', "/api/movies/{$originalMovie['id']}/recommendations?api_token={$apiToken}", [
            'recommendation' => [
                'movie_id' => $recommendedMovie['id'],
                'tmdb_id' => 0,
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testEditMovieSuccess()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::ADMIN_API_TOKEN;

        $client->request('GET', '/api/movies');
        $movies = json_decode($client->getResponse()->getContent(), true)['data'];

        $movie = reset($movies);

        $client->request('POST', "/api/movies/{$movie['id']}?api_token={$apiToken}", [
            'movie' => [
                'originalTitle' => 'new original title',
                'imdbId' => 'newImdbId',
                'runtime' => 90,
                'budget' => 123456,
                'releaseDate' => '2018-12-20',
                'translations' => [
                    ['locale' => 'en', 'title' => 'new translated title (en)', 'overview' => 'new translated overview (en)'],
                    ['locale' => 'ru', 'title' => 'new translated title (ru)', 'overview' => 'new translated overview (ru)'],
                    ['locale' => 'uk', 'title' => 'new translated title (uk)', 'overview' => 'new translated overview (uk)'],
                    ['locale' => 'pl', 'title' => 'new translated title (pl)', 'overview' => 'new translated overview (pl)'],
                ],
            ],
        ]);

        $this->assertSame(202, $client->getResponse()->getStatusCode());
        $client->request('GET', "/api/movies/{$movie['id']}?language=pl");
        $updatedMovie = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('new original title', $updatedMovie['originalTitle']);
        $this->assertSame('newImdbId', $updatedMovie['imdbId']);
        $this->assertSame(90, $updatedMovie['runtime']);
        $this->assertSame(123456, $updatedMovie['budget']);
        $this->assertSame(strtotime('2018-12-20'), strtotime($updatedMovie['releaseDate']));
        $this->assertSame('new translated title (pl)', $updatedMovie['title']);
        $this->assertSame('new translated overview (pl)', $updatedMovie['overview']);
    }

    public function testEditMovieWithoutAccess()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;

        $client->request('GET', '/api/movies');
        $movies = json_decode($client->getResponse()->getContent(), true)['data'];

        $movie = reset($movies);

        $client->request('POST', "/api/movies/{$movie['id']}?api_token={$apiToken}", [
            'movie' => [
                'originalTitle' => 'new original title',
                'imdbId' => 'newImdbId',
                'runtime' => 90,
                'budget' => 123456,
                'releaseDate' => '2018-12-20',
                'translations' => [
                    ['locale' => 'en', 'title' => 'new translated title (en)', 'overview' => 'new translated overview (en)'],
                    ['locale' => 'ru', 'title' => 'new translated title (ru)', 'overview' => 'new translated overview (ru)'],
                    ['locale' => 'uk', 'title' => 'new translated title (uk)', 'overview' => 'new translated overview (uk)'],
                    ['locale' => 'pl', 'title' => 'new translated title (pl)', 'overview' => 'new translated overview (pl)'],
                ],
            ],
        ]);

        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testFindExistingMovieInOurDatabase()
    {
        $this->checkIsApiKeyProvided();

        $client = self::$client;
        $client->request('POST', '/api/movies/search', [
            'query' => MoviesFixtures::MOVIE_TITLE, // Title of created movie
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $movies = $response['data'];
        $paging = $response['paging'];

        self::assertTrue(count($movies) > 0);
        $movie = reset($movies);
        self::assertArrayHasKey('id', $movie);
        self::assertArrayHasKey('originalTitle', $movie);
        self::assertArrayHasKey('originalPosterUrl', $movie);
        self::assertArrayHasKey('locale', $movie);
        self::assertNotEmpty($movie['id']);

        self::assertArrayHasKey('total', $paging);
        self::assertArrayHasKey('offset', $paging);
        self::assertArrayHasKey('limit', $paging);
    }

    /**
     * This movie (The 15:17 to Paris) has DRAMA genre as in our GenresFixtures
     * Test that movie will be saved correctly with all related attributes like genres/translations/tmdb data etc.
     */
    public function testFindMovieInTMDB()
    {
        $this->checkIsApiKeyProvided();

        $movieTitle = 'The 15:17 to Paris'; // Name of movie https://www.themoviedb.org/movie/453201-the-15-17-to-paris
        $client = self::$client;
        $client->request('POST', '/api/movies/search?language=ru', [
            'query' => $movieTitle,
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true)['data'];
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
        self::assertSame('ru', $movie['locale']);
        self::assertSame($movieTitle, $movie['originalTitle']);

        // Test that movies will be saved in background
        $traces = $this->getProducer($client)->getTopicTraces(MovieSyncProcessor::ADD_MOVIES_TMDB);
        $this->assertCount(1, $traces);
        $processedMovie = json_decode($traces[0]['body'], true);
        $this->assertSame($movie['tmdb']['id'], $processedMovie['id']);
    }

    public function testChangeMoviePosterSuccess()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::ADMIN_API_TOKEN;

        $client->request('GET', '/api/movies');
        $movies = json_decode($client->getResponse()->getContent(), true)['data'];

        $movie = reset($movies);

        $client->request('POST', "/api/movies/{$movie['id']}/updatePoster?api_token={$apiToken}", [
            'url' => 'http://placehold.it/1x1',
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $client->request('GET', "/api/movies/{$movie['id']}");
        $updatedMovie = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame("/f/movies/{$movie['id']}/poster.jpg", $updatedMovie['originalPosterUrl']);
        $this->assertTrue(is_file(Poster::getPath($movie['id'])));
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

    private function checkIsApiKeyProvided()
    {
        if (!\getenv('MOVIE_DB_API_KEY')) {
            echo "\r\nYou should provide MOVIE_DB_API_KEY in your .env.test\r\n";
            $this->markTestSkipped();
        }
    }
}

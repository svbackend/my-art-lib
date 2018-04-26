<?php

namespace App\Tests\Functional\Controller;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MoviesControllerTest extends WebTestCase
{
    private static $genresIds;

    private static function getGenresIds()
    {
        if (self::$genresIds) {
            return self::$genresIds;
        }

        $client = static::createClient();

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
        $client = static::createClient();
        $client->request('get', '/api/movies');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testCreateMovieWithInvalidData()
    {
        $client = static::createClient();

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
        $client = static::createClient();

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
        $client = static::createClient();
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
        self::assertArrayHasKey('translations', $response);
        self::assertArrayHasKey('genres', $response);
        self::assertArrayHasKey('release_date', $response);
        self::assertArrayHasKey('budget', $response);
        self::assertArrayHasKey('imdb_id', $response);
        self::assertArrayHasKey('original_poster_url', $response);
        self::assertArrayHasKey('original_title', $response);
    }
}
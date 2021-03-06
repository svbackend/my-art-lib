<?php

namespace App\Tests\Functional\Controller;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GenresControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testGetAllGenresWithSpecifiedLanguage()
    {
        $client = self::$client;

        $client->request('GET', '/api/genres?language=ru');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true)['data'];

        self::assertTrue(\is_array($response) && \count($response) > 0);

        $genre = reset($response); // first item of genres array
        self::assertArrayHasKey('id', $genre);
        self::assertArrayHasKey('locale', $genre);
        self::assertArrayHasKey('name', $genre);
        self::assertSame('ru', $genre['locale']);
    }

    public function testGetAllGenresWithWrongLanguage()
    {
        $client = self::$client;

        $client->request('GET', '/api/genres?language=WRONG_LANGUAGE');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true)['data'];

        self::assertTrue(\is_array($response) && \count($response) > 0);

        $genre = array_pop($response); // last item of genres array
        self::assertArrayHasKey('id', $genre);
        self::assertArrayHasKey('locale', $genre);
        self::assertArrayHasKey('name', $genre);
        self::assertSame('en', $genre['locale']);
    }

    public function testGetAllGenresWithoutLanguage()
    {
        $client = self::$client;

        $client->request('GET', '/api/genres');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true)['data'];

        self::assertTrue(\is_array($response) && \count($response) > 0);

        $genre = array_pop($response); // last item of genres array
        self::assertArrayHasKey('id', $genre);
        self::assertArrayHasKey('locale', $genre);
        self::assertArrayHasKey('name', $genre);
        self::assertSame('en', $genre['locale']);
    }

    public function testCreateGenreWithInvalidData()
    {
        $client = self::$client;

        $client->request('POST', '/api/genres', [
            'genre' => [
                'translations' => [
                    [
                        'name' => 'TestGenreName',
                        'locale' => 'INVALID_LOCALE',
                    ],
                ],
            ],
        ]);

        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('message', $response);
        self::assertArrayHasKey('errors', $response);
    }

    public function testCreateGenreWithoutPermissions()
    {
        $client = self::$client;

        $userApiToken = UsersFixtures::TESTER_API_TOKEN; // api token with role ROLE_USER
        $client->request('POST', "/api/genres?api_token={$userApiToken}", [
            'genre' => [
                'translations' => [
                    [
                        'name' => 'Valid Genre Name',
                        'locale' => 'en',
                    ],
                    [
                        'name' => 'Валидное название жанра',
                        'locale' => 'ru',
                    ],
                ],
            ],
        ]);

        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testCreateGenreSuccess()
    {
        $client = self::$client;

        $adminApiToken = UsersFixtures::ADMIN_API_TOKEN;
        $client->request('POST', "/api/genres?api_token={$adminApiToken}", [
            'genre' => [
                'translations' => [
                    [
                        'name' => 'Valid Genre Name',
                        'locale' => 'en',
                    ],
                    [
                        'name' => 'Валидное название жанра',
                        'locale' => 'ru',
                    ],
                ],
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('id', $response);
        self::assertArrayHasKey('name', $response);
        self::assertArrayHasKey('locale', $response);
    }

    public function testUpdateGenreSuccess()
    {
        $client = self::$client;
        $adminApiToken = UsersFixtures::ADMIN_API_TOKEN;

        $client->request('GET', '/api/genres');
        $response = json_decode($client->getResponse()->getContent(), true);
        $firstGenre = reset($response['data']);

        $client->request('POST', "/api/genres/{$firstGenre['id']}?api_token={$adminApiToken}", [
            'genre' => [
                'translations' => [
                    [
                        'name' => 'Updated Genre',
                        'locale' => 'en',
                    ],
                    [
                        'name' => 'Обновлённый жанр',
                        'locale' => 'ru',
                    ],
                ],
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('id', $response);
        self::assertArrayHasKey('name', $response);
        self::assertArrayHasKey('locale', $response);
        self::assertSame('Updated Genre', $response['name']);
    }
}

<?php

namespace App\Tests\Functional\Controller;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GenresControllerTest extends WebTestCase
{
    public function testGetAllGenresWithSpecifiedLanguage()
    {
        $client = static::createClient();

        $client->request('GET', "/api/genres?language=ru");

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertTrue(is_array($response) && count($response) > 0);

        $genre = array_pop($response); // last item of genres array
        self::assertArrayHasKey('id', $genre);
        self::assertArrayHasKey('locale', $genre);
        self::assertArrayHasKey('name', $genre);
        self::assertEquals('ru', $genre['locale']);

    }

    public function testGetAllGenresWithWrongLanguage()
    {
        $client = static::createClient();

        $client->request('GET', "/api/genres?language=WRONG_LANGUAGE");

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertTrue(is_array($response) && count($response) > 0);

        $genre = array_pop($response); // last item of genres array
        self::assertArrayHasKey('id', $genre);
        self::assertArrayHasKey('locale', $genre);
        self::assertArrayHasKey('name', $genre);
        self::assertEquals('en', $genre['locale']);

    }

    public function testGetAllGenresWithoutLanguage()
    {
        $client = static::createClient();

        $client->request('GET', "/api/genres");

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertTrue(is_array($response) && count($response) > 0);

        $genre = array_pop($response); // last item of genres array
        self::assertArrayHasKey('id', $genre);
        self::assertArrayHasKey('locale', $genre);
        self::assertArrayHasKey('name', $genre);
        self::assertEquals('en', $genre['locale']);

    }

    public function testCreateGenreWithInvalidData()
    {
        $client = static::createClient();

        $client->request('POST', "/api/genres", [
            'genre' => [
                'translations' => [
                    [
                        'name' => 'TestGenreName',
                        'locale' => 'INVALID_LOCALE',
                    ],
                ]
            ]
        ]);

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('message', $response);
        self::assertArrayHasKey('errors', $response);

    }

    public function testCreateGenreWithoutPermissions()
    {
        $client = static::createClient();

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
                ]
            ]
        ]);

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testCreateGenreSuccess()
    {
        $client = static::createClient();

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
                ]
            ]
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('id', $response);
        self::assertArrayHasKey('name', $response);
        self::assertArrayHasKey('locale', $response);
    }

    public function testUpdateGenreSuccess()
    {
        $client = static::createClient();

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
                ]
            ]
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', "/api/genres/{$response['id']}?api_token={$adminApiToken}", [
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
                ]
            ]
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('id', $response);
        self::assertArrayHasKey('name', $response);
        self::assertArrayHasKey('locale', $response);
        self::assertEquals('Updated Genre', $response['name']);
    }
}
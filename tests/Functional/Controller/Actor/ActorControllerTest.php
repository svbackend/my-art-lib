<?php

namespace App\Tests\Functional\Controller\Actor;

use App\Actors\Entity\Actor;
use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActorControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testEditActorSuccess()
    {
        $client = self::$client;

        $client->request('GET', '/api/actors');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $actor = reset($response['data']);

        $token = UsersFixtures::ADMIN_API_TOKEN;
        $client->request('POST', "/api/actors/{$actor['id']}?api_token={$token}", [
            'actor' => [
                'originalName' => 'new name',
                'imdbId' => 'new imdb id',
                'birthday' => '1980-12-30',
                'gender' => Actor::GENDER_FEMALE,
                'translations' => [
                    [
                        'locale' => 'en',
                        'name' => 'name (en)',
                        'placeOfBirth' => 'place of birth (en)',
                        'biography' => 'biography (en)',
                    ],
                ],
            ],
        ]);

        $this->assertSame(202, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/actors/{$actor['id']}");
        $actor = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('new name', $actor['originalName']);
        $this->assertSame('new imdb id', $actor['imdbId']);
        $this->assertSame(strtotime('1980-12-30'), strtotime($actor['birthday']));
        $this->assertSame(Actor::GENDER_FEMALE, $actor['gender']);
        $this->assertSame('name (en)', $actor['name']);
        $this->assertSame('place of birth (en)', $actor['placeOfBirth']);
        $this->assertSame('biography (en)', $actor['biography']);
    }

    public function testEditActorWithNewTranslationSuccess()
    {
        $client = self::$client;

        $client->request('GET', '/api/actors');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $actor = reset($response['data']);

        $token = UsersFixtures::ADMIN_API_TOKEN;
        $client->request('POST', "/api/actors/{$actor['id']}?api_token={$token}", [
            'actor' => [
                'originalName' => 'new name',
                'imdbId' => 'new imdb id',
                'birthday' => '1980-12-30',
                'gender' => Actor::GENDER_FEMALE,
                'translations' => [
                    [
                        'locale' => 'pl',
                        'name' => 'name (pl)',
                        'placeOfBirth' => 'place of birth (pl)',
                        'biography' => 'biography (pl)',
                    ],
                ],
            ],
        ]);

        $this->assertSame(202, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/actors/{$actor['id']}?language=pl");
        $actor = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('name (pl)', $actor['name']);
        $this->assertSame('place of birth (pl)', $actor['placeOfBirth']);
        $this->assertSame('biography (pl)', $actor['biography']);
    }
}

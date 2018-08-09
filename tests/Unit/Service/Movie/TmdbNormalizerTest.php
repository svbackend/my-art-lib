<?php

namespace App\Tests\Unit\Service\Movie;

use App\Actors\Entity\Actor;
use App\Genres\Repository\GenreRepository;
use App\Movies\Service\MovieManageService;
use App\Movies\Service\TmdbNormalizerService;
use App\Users\DataFixtures\UsersFixtures;
use App\Users\Entity\ApiToken;
use App\Users\Entity\User;
use App\Users\Repository\UserRepository;
use App\Users\Request\AuthUserRequest;
use App\Users\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\TranslatorInterface;

class AuthServiceTest extends KernelTestCase
{
    /**
     * @var MovieManageService|MockObject
     */
    private $movieManager;

    /**
     * @var GenreRepository|MockObject
     */
    private $genreRepository;

    /**
     * @var TmdbNormalizerService
     */
    private $normalizer;

    public function setUp()
    {
        $this->movieManager = $this->createMock(MovieManageService::class);
        $this->genreRepository = $this->createMock(GenreRepository::class);
        $this->normalizer = new TmdbNormalizerService($this->movieManager, $this->genreRepository);
    }

    public function testActorNormalizationSuccess()
    {
        $actor = [
            'id' => 1,
            'name' => 'Test Actor',
            'birthday' => '1980-12-28',
            'imdb_id' => 'test_imdb_id',
            'gender' => 1,
            'profile_path' => '/photo.jpg',
            'biography' => 'test biography',
        ];

        /** @var $actor Actor */
        $actor = $this->normalizer->normalizeActorsToObjects([$actor])->current();
        $this->assertEquals('Test Actor', $actor->getOriginalName());
        $this->assertEquals(strtotime('1980-12-28'), $actor->getBirthday()->getTimestamp());
        $this->assertEquals(1, $actor->getTmdb()->getId());
        $this->assertEquals('test_imdb_id', $actor->getImdbId());
        $this->assertEquals(1, $actor->getGender());
        $imageFilename = explode('/', $actor->getPhoto());
        $this->assertEquals('photo.jpg', end($imageFilename));
        $this->assertEquals('Test Actor', $actor->getTranslation('en', false)->getName());
        $this->assertEquals('test biography', $actor->getTranslation('en', false)->getBiography());

    }

    public function testActorNormalizationWithoutSomeData()
    {
        $actor = [
            'id' => 1,
            'name' => 'Test Actor',
        ];

        /** @var $actor Actor */
        $actor = $this->normalizer->normalizeActorsToObjects([$actor])->current();
        $this->assertEquals('Test Actor', $actor->getOriginalName());
        $this->assertEquals(1, $actor->getTmdb()->getId());
        $this->assertEquals('', $actor->getImdbId());
        $this->assertEquals($actor::GENDER_MALE, $actor->getGender());
        $this->assertEquals('', $actor->getPhoto());
        $this->assertEquals('Test Actor', $actor->getTranslation('en', false)->getName());
        $this->assertEquals('', $actor->getTranslation('en', false)->getBiography());

    }
}
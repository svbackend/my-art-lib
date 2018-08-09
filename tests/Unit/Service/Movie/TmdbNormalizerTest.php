<?php

namespace App\Tests\Unit\Service\Movie;

use App\Actors\Entity\Actor;
use App\Genres\Repository\GenreRepository;
use App\Movies\Service\MovieManageService;
use App\Movies\Service\TmdbNormalizerService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TmdbNormalizerTest extends KernelTestCase
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
        $this->assertSame('Test Actor', $actor->getOriginalName());
        $this->assertSame(strtotime('1980-12-28'), $actor->getBirthday()->getTimestamp());
        $this->assertSame(1, $actor->getTmdb()->getId());
        $this->assertSame('test_imdb_id', $actor->getImdbId());
        $this->assertSame(1, $actor->getGender());
        $imageFilename = explode('/', $actor->getPhoto());
        $this->assertSame('photo.jpg', end($imageFilename));
        $this->assertSame('Test Actor', $actor->getTranslation('en', false)->getName());
        $this->assertSame('test biography', $actor->getTranslation('en', false)->getBiography());
    }

    public function testActorNormalizationWithoutSomeData()
    {
        $actor = [
            'id' => 1,
            'name' => 'Test Actor',
        ];

        /** @var $actor Actor */
        $actor = $this->normalizer->normalizeActorsToObjects([$actor])->current();
        $this->assertSame('Test Actor', $actor->getOriginalName());
        $this->assertSame(1, $actor->getTmdb()->getId());
        $this->assertSame('', $actor->getImdbId());
        $this->assertSame($actor::GENDER_MALE, $actor->getGender());
        $this->assertSame('', $actor->getPhoto());
        $this->assertSame('Test Actor', $actor->getTranslation('en', false)->getName());
        $this->assertSame('', $actor->getTranslation('en', false)->getBiography());
    }
}

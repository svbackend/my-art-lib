<?php
declare(strict_types=1);

namespace App\Service\Genre;

use App\Entity\Genre;
use App\Entity\Translations\GenreTranslations;
use App\Request\Genre\CreateGenreRequest;
use App\Request\Genre\UpdateGenreRequest;
use App\Translation\TranslatedEntityHelper;
use Doctrine\ORM\EntityManagerInterface;

class GenreManageService
{
    private $translatedEntityHelper;
    private $entityManager;

    public function __construct(TranslatedEntityHelper $translatedEntityHelper, EntityManagerInterface $entityManager)
    {
        $this->translatedEntityHelper = $translatedEntityHelper;
        $this->entityManager = $entityManager;
    }

    public function createGenreByRequest(CreateGenreRequest $request): Genre
    {
        return $this->createGenre($request->get('genre')['translations']);
    }

    public function updateGenreByRequest(UpdateGenreRequest $request, Genre $genre): Genre
    {
        return $this->updateGenre($genre, $request->get('genre')['translations']);
    }

    public function createGenre(array $translations): Genre
    {
        $genre = new Genre();

        $addTranslation = function ($translation) use ($genre) {
            $genre->addTranslation(
                new GenreTranslations($genre, $translation['locale'], $translation['name'])
            );
        };

        $this->translatedEntityHelper->updateTranslations($genre, $translations, $addTranslation);
        $this->entityManager->persist($genre);

        return $genre;
    }

    public function updateGenre(Genre $genre, array $translations): Genre
    {
        $addTranslation = function (array $translation) use ($genre) {
            $genre->addTranslation(
                new GenreTranslations($genre, $translation['locale'], $translation['name'])
            );
        };

        $updateTranslation = function (array $translation, GenreTranslations $oldTranslation) {
            $oldTranslation->changeName($translation['name']);
        };

        $this->translatedEntityHelper->updateTranslations($genre, $translations, $addTranslation, $updateTranslation);
        $this->entityManager->persist($genre);

        return $genre;
    }
}
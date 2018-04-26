<?php
declare(strict_types=1);

namespace App\Genres\Service;

use App\Genres\Entity\Genre;
use App\Genres\Entity\GenreTranslations;
use App\Genres\Request\CreateGenreRequest;
use App\Genres\Request\UpdateGenreRequest;
use Doctrine\ORM\EntityManagerInterface;

class GenreManageService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
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

        $genre->updateTranslations($translations, $addTranslation);
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

        $genre->updateTranslations($translations, $addTranslation, $updateTranslation);
        $this->entityManager->persist($genre);

        return $genre;
    }
}
<?php

namespace App\Genres\Controller;

use App\Controller\BaseController;
use App\Genres\Entity\Genre;
use App\Genres\Repository\GenreRepository;
use App\Genres\Request\CreateGenreRequest;
use App\Genres\Request\UpdateGenreRequest;
use App\Genres\Service\GenreManageService;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\UserRoles;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class GenreController.
 */
class GenreController extends BaseController
{
    /**
     * Get all genres.
     *
     * @Route("/api/genres", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function getAll(GenreRepository $genreRepository)
    {
        $genres = $genreRepository->findAllWithTranslations();
        $genres = new PaginatedCollection($genres->getQuery(), 0, 20);

        return $this->response($genres, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * Create new genre.
     *
     * @Route("/api/genres", methods={"POST"})
     *
     * @param \App\Genres\Request\CreateGenreRequest $request
     * @param \App\Genres\Service\GenreManageService $genreManageService
     * @param ValidatorInterface                     $validator
     *
     * @return Genre|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function postGenres(CreateGenreRequest $request, GenreManageService $genreManageService, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_ADMIN);

        $genre = $genreManageService->createGenreByRequest($request);
        $errors = $validator->validate($genre);

        if (\count($errors)) {
            return $request->getErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->response($genre, 200, [], [
            'groups' => ['view'],
        ]);
    }

    /**
     * Update genre.
     *
     * @Route("/api/genres/{id}", methods={"POST"})
     *
     * @param UpdateGenreRequest                     $request
     * @param Genre                                  $genre
     * @param \App\Genres\Service\GenreManageService $genreManageService
     * @param ValidatorInterface                     $validator
     *
     * @return Genre|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function putGenres(UpdateGenreRequest $request, Genre $genre, GenreManageService $genreManageService, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_ADMIN);

        $genre = $genreManageService->updateGenreByRequest($request, $genre);
        $errors = $validator->validate($genre);

        if (\count($errors)) {
            return $request->getErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->response($genre, 200, [], [
            'groups' => ['view'],
        ]);
    }
}

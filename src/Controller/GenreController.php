<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Entity\Translations\GenreTranslations;
use App\Entity\User;
use App\Request\Genre\CreateGenreRequest;
use App\Request\Genre\UpdateGenreRequest;
use App\Service\Genre\GenreManageService;
use App\Translation\TranslatedEntityHelper;
use App\Translation\TranslatedEntitySerializer;
use function foo\func;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class GenreController
 * @package App\Controller
 */
class GenreController extends FOSRestController
{
    /**
     * Get all genres
     *
     * @Route("/api/genres", methods={"GET"})
     * @SWG\Response(
     *     description="REST action which returns all genres.",
     *     response=200,
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Genre::class, groups={"full"}))
     *     )
     * )
     *
     * @return array
     */
    public function getAll()
    {
        return $this->getDoctrine()->getRepository(Genre::class)->findAll();

    }

    /**
     * Create new genre
     *
     * @Route("/api/genres", methods={"POST"})
     * @SWG\Parameter(name="genre[translations][0][locale]", in="formData", type="string")
     * @SWG\Parameter(name="genre[translations][0][name]", in="formData", type="string")
     * @SWG\Response(
     *     description="New genre action.",
     *     response=202,
     *     @Model(type=Genre::class)
     * )
     * @param CreateGenreRequest $request
     * @param GenreManageService $genreManageService
     * @param ValidatorInterface $validator
     * @return Genre|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function postGenres(CreateGenreRequest $request, GenreManageService $genreManageService, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $genre = $genreManageService->createGenreByRequest($request);
        $errors = $validator->validate($genre);

        if (count($errors)) {
            return $request->getErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return $genre;
    }

    /**
     * Update genre
     *
     * @Route("/api/genres/{id}", methods={"POST"})
     * @SWG\Parameter(name="genre[translations][0][locale]", in="formData", type="string")
     * @SWG\Parameter(name="genre[translations][0][name]", in="formData", type="string")
     * @SWG\Response(
     *     description="New genre action.",
     *     response=202,
     *     @Model(type=Genre::class)
     * )
     * @param UpdateGenreRequest $request
     * @param Genre $genre
     * @param GenreManageService $genreManageService
     * @param ValidatorInterface $validator
     * @return Genre|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function putGenres(UpdateGenreRequest $request, Genre $genre, GenreManageService $genreManageService, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $genre = $genreManageService->updateGenreByRequest($request, $genre);
        $errors = $validator->validate($genre);

        if (count($errors)) {
            return $request->getErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return $genre;
    }
}
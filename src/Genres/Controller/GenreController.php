<?php

namespace App\Genres\Controller;

use App\Controller\BaseController;
use App\Controller\ControllerInterface;
use App\Genres\Entity\Genre;
use App\Users\Entity\User;
use App\Genres\Request\CreateGenreRequest;
use App\Genres\Request\UpdateGenreRequest;
use App\Genres\Service\GenreManageService;
use App\Users\Entity\UserRoles;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use FOS\RestBundle\Controller\Annotations\View;

/**
 * Class GenreController
 * @package App\Genres\Controller
 */
class GenreController extends BaseController
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
     * @View(serializerGroups={"list"})
     *
     * @return JsonResponse
     */
    public function getAll()
    {
        $genres = $this->getDoctrine()->getRepository(Genre::class)->findAll();
        return $this->response($genres, 200, [], [
            'groups' => ['list'],
        ]);
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
     * @param \App\Genres\Request\CreateGenreRequest $request
     * @param \App\Genres\Service\GenreManageService $genreManageService
     * @param ValidatorInterface $validator
     * @return Genre|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function postGenres(CreateGenreRequest $request, GenreManageService $genreManageService, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_ADMIN);

        $genre = $genreManageService->createGenreByRequest($request);
        $errors = $validator->validate($genre);

        if (count($errors)) {
            return $request->getErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->response($genre, 200, [], [
            'groups' => ['view'],
        ]);
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
     * @param \App\Genres\Service\GenreManageService $genreManageService
     * @param ValidatorInterface $validator
     * @return Genre|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function putGenres(UpdateGenreRequest $request, Genre $genre, GenreManageService $genreManageService, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_ADMIN);

        $genre = $genreManageService->updateGenreByRequest($request, $genre);
        $errors = $validator->validate($genre);

        if (count($errors)) {
            return $request->getErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->response($genre, 200, [], [
            'groups' => ['view'],
        ]);
    }
}
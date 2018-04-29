<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Controller\ControllerInterface;
use App\Movies\Entity\Movie;
use App\Movies\Request\CreateMovieRequest;
use App\Movies\Service\MovieManageService;
use App\Users\Entity\UserRoles;
use FOS\RestBundle\Controller\FOSRestController;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class MovieController
 * @package App\Movies\Controller
 */
class MovieController extends BaseController
{
    /**
     * Get all movies
     *
     * @Route("/api/movies", methods={"GET"})
     * @SWG\Response(
     *     description="REST action which returns all movies.",
     *     response=200,
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Movie::class))
     *     )
     * )
     *
     * @return array
     */
    public function getAll()
    {
        $movies = $this->getDoctrine()->getRepository(Movie::class)->findAll();
        return $this->response($movies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * Create new movie
     *
     * @Route("/api/movies", methods={"POST"})
     * @SWG\Parameter(name="movie[originalTitle]", in="formData", type="string")
     * @SWG\Parameter(name="movie[posterUrl]", in="formData", type="string")
     * @SWG\Parameter(name="movie[translations][0][locale]", in="formData", type="string")
     * @SWG\Parameter(name="movie[translations][0][title]", in="formData", type="string")
     * @SWG\Parameter(name="movie[translations][0][posterUrl]", in="formData", type="string")
     * @SWG\Parameter(name="movie[translations][0][overview]", in="formData", type="string")
     * @SWG\Parameter(name="movie[tmdb][id]", in="formData", type="integer")
     * @SWG\Parameter(name="movie[tmdb][voteAverage]", in="formData", type="number")
     * @SWG\Parameter(name="movie[tmdb][voteCount]", in="formData", type="integer")
     * @SWG\Response(
     *     description="New movie action.",
     *     response=202,
     *     @Model(type=Movie::class)
     * )
     *
     * @param CreateMovieRequest $request
     * @param MovieManageService $service
     * @param ValidatorInterface $validator
     * @return Movie|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function postMovies(CreateMovieRequest $request, MovieManageService $service, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_ADMIN);

        $movie = $service->createMovieByRequest($request);
        $errors = $validator->validate($movie);

        if (count($errors)) {
            return $request->getErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return $movie;
    }
}
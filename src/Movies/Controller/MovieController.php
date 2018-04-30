<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Movies\Entity\Movie;
use App\Movies\Request\CreateMovieRequest;
use App\Movies\Service\MovieManageService;
use App\Users\Entity\UserRoles;
use Symfony\Component\Routing\Annotation\Route;
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

        return $this->response($movie, 200, [], [
            'groups' => ['view'],
        ]);
    }
}
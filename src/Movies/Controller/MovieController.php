<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Movies\Entity\Movie;
use App\Movies\Request\CreateMovieRequest;
use App\Movies\Request\SearchRequest;
use App\Movies\Service\MovieManageService;
use App\Movies\Service\SearchService;
use App\Users\Entity\UserRoles;
use Symfony\Component\HttpFoundation\Request;
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
     */
    public function getAll()
    {
        $movies = $this->getDoctrine()->getRepository(Movie::class)->findAll();
        return $this->response($movies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * Get movies by title
     *
     * @Route("/api/movies/search", methods={"POST"})
     * @param SearchRequest $request
     * @param SearchService $searchService
     * @param Request $currentRequest
     * @throws \Exception
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSearch(SearchRequest $request, SearchService $searchService, Request $currentRequest)
    {
        $query = $request->get('query');
        $movies = $searchService->findByQuery($query, $currentRequest->getLocale());

        return $this->response($movies, 200, [], [
            'groups' => ['list']
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
     * @throws \Exception
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function postMovies(CreateMovieRequest $request, MovieManageService $service, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_ADMIN);

        $movie = $service->createMovieByRequest($request);
        $errors = $validator->validate($movie);

        if (count($errors)) {
            return $request->getErrorResponse($errors);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($movie);
        $entityManager->flush();

        return $this->response($movie, 200, [], [
            'groups' => ['view'],
        ]);
    }
}
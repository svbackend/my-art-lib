<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Movies\Entity\Movie;
use App\Movies\Repository\MovieRepository;
use App\Movies\Request\CreateMovieRequest;
use App\Movies\Request\SearchRequest;
use App\Movies\Service\MovieManageService;
use App\Movies\Service\SearchService;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\User;
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
     *
     * @param Request $request
     * @param MovieRepository $movieRepository
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAll(Request $request, MovieRepository $movieRepository)
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            $movies = $movieRepository->findAllWithIsUserWatchedFlag($user);
        } else {
            $movies = $movieRepository->findAllWithIsGuestWatchedFlag($this->getGuest());
        }

        $offset = (int)$request->get('offset', 0);
        $limit = $request->get('limit', null);

        $movies = new PaginatedCollection($movies, $offset, $limit);

        return $this->response($movies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * Get movie resource
     *
     * @Route("/api/movies/{id}", methods={"GET"})
     * @param Movie $movie
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getMovies(Movie $movie)
    {
        return $this->response($movie, 200, [], [
            'groups' => ['view'],
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
        $offset = (int)$request->get('offset', 0);
        $limit = $request->get('limit', null);

        $query = $request->get('query');
        $movies = $searchService->findByQuery($query, $currentRequest->getLocale(), $offset, $limit);

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
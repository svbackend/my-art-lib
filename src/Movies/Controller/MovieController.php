<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Movies\DTO\MovieTranslationDTO;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTranslations;
use App\Movies\EventListener\SimilarMoviesProcessor;
use App\Movies\Repository\MovieRepository;
use App\Movies\Request\CreateMovieRequest;
use App\Movies\Request\SearchRequest;
use App\Movies\Request\UpdateMovieRequest;
use App\Movies\Service\MovieManageService;
use App\Movies\Service\SearchService;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\User;
use App\Users\Entity\UserRoles;
use Enqueue\Client\ProducerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class MovieController.
 */
class MovieController extends BaseController
{
    /**
     * Get all movies.
     *
     * @Route("/api/movies", methods={"GET"})
     *
     * @param Request         $request
     * @param MovieRepository $movieRepository
     *
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

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $movies = new PaginatedCollection($movies, $offset, $limit);

        return $this->response($movies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * Get movie resource.
     *
     * @Route("/api/movies/{id}", methods={"GET"})
     *
     * @param int               $id
     * @param MovieRepository   $repository
     * @param ProducerInterface $producer
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return JsonResponse
     */
    public function getMovies(int $id, MovieRepository $repository, ProducerInterface $producer)
    {
        if (null === $movie = $repository->findOneForMoviePage($id, $this->getUser())) {
            throw new NotFoundHttpException();
        }

        if (count($movie->getSimilarMovies()) === 0) {
            $producer->sendEvent(SimilarMoviesProcessor::LOAD_SIMILAR_MOVIES, json_encode($movie->getId()));
        }

        return $this->response($movie, 200, [], [
            'groups' => ['view'],
        ]);
    }

    /**
     * Get movies by title.
     *
     * @Route("/api/movies/search", methods={"POST"})
     *
     * @param SearchRequest $request
     * @param SearchService $searchService
     * @param Request       $currentRequest
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSearch(SearchRequest $request, SearchService $searchService, Request $currentRequest)
    {
        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $query = $request->get('query');
        $movies = $searchService->findByQuery($query, $currentRequest->getLocale(), $offset, $limit);

        return $this->response($movies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * Create new movie.
     *
     * @Route("/api/movies", methods={"POST"})
     *
     * @param CreateMovieRequest $request
     * @param MovieManageService $service
     * @param ValidatorInterface $validator
     *
     * @throws \Exception
     *
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

    /**
     * @Route("/api/movies/{id}", methods={"POST", "PUT", "PATCH"}, requirements={"id"="\d+"})
     *
     * @param Movie              $movie
     * @param UpdateMovieRequest $request
     *
     * @throws \ErrorException
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function putMovies(Movie $movie, UpdateMovieRequest $request)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_ADMIN);

        $movieData = $request->get('movie');
        $movieTranslationsData = $movieData['translations'];

        $movie->setOriginalTitle($movieData['originalTitle']);
        $movie->setImdbId($movieData['imdbId']);
        $movie->setRuntime($movieData['runtime']);
        $movie->setBudget($movieData['budget']);
        $movie->setReleaseDate(new \DateTimeImmutable($movieData['releaseDate']));

        $addTranslation = function (array $trans) use ($movie) {
            $transDto = new MovieTranslationDTO($trans['locale'], $trans['title'], $trans['overview'], null);
            $movie->addTranslation(
                new MovieTranslations($movie, $transDto)
            );
        };

        $updateTranslation = function (array $trans, MovieTranslations $oldTranslation) use ($movie) {
            $oldTranslation->setTitle($trans['title']);
            $oldTranslation->setOverview($trans['overview']);
        };

        $movie->updateTranslations($movieTranslationsData, $addTranslation, $updateTranslation);

        $em = $this->getDoctrine()->getManager();
        $em->persist($movie); // if there 1+ new translations lets persist movie to be sure that they will be saved
        $em->flush();

        return new JsonResponse(null, 202);
    }
}

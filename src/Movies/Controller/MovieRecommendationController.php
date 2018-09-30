<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieRecommendation;
use App\Movies\EventListener\AddRecommendationProcessor;
use App\Movies\Repository\MovieRecommendationRepository;
use App\Movies\Repository\MovieRepository;
use App\Movies\Request\NewMovieRecommendationRequest;
use App\Movies\Request\RemoveMovieRecommendationRequest;
use App\Movies\Request\SearchRequest;
use App\Movies\Service\SearchService;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\User;
use App\Users\Entity\UserRoles;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\ProducerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class MovieRecommendationController extends BaseController
{
    /**
     * Add new recommendation.
     *
     * @Route("/api/movies/{id}/recommendations", methods={"POST"})
     *
     * @param NewMovieRecommendationRequest $request
     * @param Movie                         $originalMovie
     * @param EntityManagerInterface        $em
     * @param ProducerInterface             $producer
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return JsonResponse
     */
    public function postMoviesRecommendations(NewMovieRecommendationRequest $request, Movie $originalMovie, EntityManagerInterface $em, ProducerInterface $producer)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_USER);

        $recommendation = $request->get('recommendation');
        $user = $this->getUser();

        if (empty($recommendation['movie_id'])) {
            $message = new Message(json_encode([
                'tmdb_id' => $recommendation['tmdb_id'],
                'movie_id' => $originalMovie->getId(),
                'user_id' => $user->getId(),
            ]));
            $message->setPriority(MessagePriority::VERY_LOW);
            $producer->sendEvent(AddRecommendationProcessor::ADD_RECOMMENDATION, $message);

            return new JsonResponse();
        }

        $recommendedMovie = $em->getReference(Movie::class, $recommendation['movie_id']);

        if ($recommendedMovie === null) {
            throw new NotFoundHttpException();
        }

        $originalMovie->addRecommendation($user, $recommendedMovie);
        $em->persist($originalMovie);
        try {
            $em->flush();
        } catch (UniqueConstraintViolationException $exception) {
            // It's ok..
        }

        return new JsonResponse();
    }

    /**
     * Remove recommendation.
     *
     * @Route("/api/movies/{id}/recommendations", methods={"DELETE"})
     *
     * @param RemoveMovieRecommendationRequest $request
     * @param Movie                            $originalMovie
     * @param MovieRecommendationRepository    $repository
     * @param EntityManagerInterface           $em
     *
     * @return JsonResponse
     */
    public function deleteMoviesRecommendations(RemoveMovieRecommendationRequest $request, Movie $originalMovie, MovieRecommendationRepository $repository, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_USER);

        $user = $this->getUser();

        if (empty($request->get('movie_id'))) {
            $recommendedMovie = $this->findRecommendedMovieByTmdbId($originalMovie, $user, $request->get('tmdb_id'));
            if ($recommendedMovie === null) {
                return new JsonResponse();
            }

            $em->remove($recommendedMovie);
            $em->flush();

            return new JsonResponse();
        }

        $recommendedMovie = $this->findRecommendedMovieById($originalMovie, $user, $request->get('movie_id'));
        if ($recommendedMovie === null) {
            return new JsonResponse();
        }

        $em->remove($recommendedMovie);
        $em->flush();

        return new JsonResponse();
    }

    private function findRecommendedMovieById(Movie $originalMovie, User $user, int $id): ?MovieRecommendation
    {
        foreach ($originalMovie->getRecommendations() as $recommendation) {
            if ($recommendation->getUser()->getId() === $user->getId() && $recommendation->getRecommendedMovie()->getId() === $id) {
                return $recommendation;
            }
        }

        return null;
    }

    private function findRecommendedMovieByTmdbId(Movie $originalMovie, User $user, int $tmdbId): ?MovieRecommendation
    {
        foreach ($originalMovie->getRecommendations() as $recommendation) {
            if ($recommendation->getUser()->getId() === $user->getId() && $recommendation->getRecommendedMovie()->getTmdb()->getId() === $tmdbId) {
                return $recommendation;
            }
        }

        return null;
    }

    /**
     * @Route("/api/movies/{id}/recommendations", methods={"GET"})
     *
     * @param Request $request
     * @param Movie $movie
     * @param MovieRecommendationRepository $repository
     *
     * @return JsonResponse
     */
    public function getMoviesRecommendations(Request $request, Movie $movie, MovieRecommendationRepository $repository)
    {
        $user = $this->getUser();

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        if ($user instanceof User) {
            $recommendedMovies = $repository->findAllByMovieAndUser($movie->getId(), $user->getId());
        } else {
            $recommendedMovies = $repository->findAllByMovie($movie->getId());
        }

        $recommendedMovies = new PaginatedCollection($recommendedMovies, $offset, $limit, false);

        return $this->response($recommendedMovies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * Get movies by title.
     *
     * @Route("/api/movies/{id}/recommendations/search", methods={"POST"})
     *
     * @param int           $id
     * @param SearchRequest $request
     * @param SearchService $searchService
     * @param Request       $currentRequest
     *
     * @throws \App\Movies\Exception\TmdbMovieNotFoundException
     * @throws \App\Movies\Exception\TmdbRequestLimitException
     * @throws \ErrorException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSearch(int $id, SearchRequest $request, SearchService $searchService, Request $currentRequest)
    {
        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $query = $request->get('query');

        if (null === $user = $this->getUser()) {
            $movies = $searchService->findByQuery($query, $currentRequest->getLocale(), $offset, $limit);
        } else {
            $movies = $searchService->findByQueryWithUserRecommendedMovie($query, $id, $user->getId(), $currentRequest->getLocale(), $offset, $limit);
        }

        return $this->response($movies, 200, [], [
            'groups' => ['list'],
        ]);
    }
}

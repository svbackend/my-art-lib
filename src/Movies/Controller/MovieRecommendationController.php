<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Movies\Entity\Movie;
use App\Movies\EventListener\AddRecommendationProcessor;
use App\Movies\Repository\MovieRecommendationRepository;
use App\Movies\Repository\MovieRepository;
use App\Movies\Request\NewMovieRecommendationRequest;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\User;
use App\Users\Entity\UserRoles;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
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

        if (!empty($recommendation['movie_id'])) {
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

        $producer->sendEvent(AddRecommendationProcessor::ADD_RECOMMENDATION, json_encode([
            'tmdb_id' => $recommendation['tmdb_id'],
            'movie_id' => $originalMovie->getId(),
            'user_id' => $user->getId(),
        ]));

        return new JsonResponse();
    }

    /**
     * @Route("/api/movies/{id}/recommendations", methods={"GET"})
     *
     * @param Movie                         $movie
     * @param MovieRepository               $movieRepository
     * @param MovieRecommendationRepository $repository
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return JsonResponse
     */
    public function getMoviesRecommendations(Movie $movie, MovieRepository $movieRepository, MovieRecommendationRepository $repository)
    {
        $user = $this->getUser();
        $sortRecommendedMovies = function (array $movie1, array $movie2) {
            return $movie2['rate'] <=> $movie1['rate'];
        };

        if ($user instanceof User) {
            $recommendedMoviesIds = $repository->findAllByMovieAndUser($movie->getId(), $user->getId());
            usort($recommendedMoviesIds, $sortRecommendedMovies);
            $recommendedMovies = $movieRepository->findAllByIdsWithFlags(array_map(function (array $recommendedMovie) {
                return $recommendedMovie['movie_id'];
            }, $recommendedMoviesIds), $user->getId());
        } else {
            $recommendedMoviesIds = $repository->findAllByMovie($movie->getId());
            usort($recommendedMoviesIds, $sortRecommendedMovies);
            $recommendedMovies = $movieRepository->findAllByIdsWithoutFlags(array_map(function (array $recommendedMovie) {
                return $recommendedMovie['movie_id'];
            }, $recommendedMoviesIds));
        }

        return $this->response($recommendedMovies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * @Route("/api/recommendations", methods={"GET"})
     *
     * @param Request                       $request
     * @param MovieRecommendationRepository $repository
     *
     * @return JsonResponse
     */
    public function getAllRecommendations(Request $request, MovieRecommendationRepository $repository)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_USER);
        $user = $this->getUser();

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);
        $minRating = $request->get('minRating', 7);

        $query = $repository->findAllByUser($user->getId(), abs((int) $minRating));
        $movies = new PaginatedCollection($query, $offset, $limit, false);

        return $this->response($movies, 200, [], [
            'groups' => ['list'],
        ]);
    }
}

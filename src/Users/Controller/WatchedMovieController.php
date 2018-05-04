<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Movies\DTO\WatchedMovieDTO;
use App\Movies\Entity\Movie;
use App\Movies\Entity\UserWatchedMovie;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\SearchService;
use App\Users\Request\AddWatchedMovieRequest;
use App\Users\Service\WatchedMovieService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class WatchedMovieController extends BaseController
{
    /**
     * @Route("/api/users/watchedMovies", methods={"POST"});
     * @param AddWatchedMovieRequest $addWatchedMovieRequest
     * @param Request $request
     * @param WatchedMovieService $watchedMovieService
     * @return JsonResponse
     * @throws \Exception|NotFoundHttpException
     */
    public function postWatchedMovies(AddWatchedMovieRequest $addWatchedMovieRequest, Request $request, WatchedMovieService $watchedMovieService)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $movieData = $addWatchedMovieRequest->get('movie');
        $movieId = (int)$movieData['id'] ?? null;
        $movieTmdbId = (int)$movieData['tmdbId'] ?? null;
        $vote = (float)$movieData['vote'] ?? null;
        $watchedAt = !empty($movieData['watchedAt']) ? new \DateTimeImmutable($movieData['watchedAt']) : null;

        $watchedMovieDTO = new WatchedMovieDTO($movieId, $movieTmdbId, $vote, $watchedAt);
        $isMovieAdded = $watchedMovieService->addWatchedMovie($this->getUser(), $watchedMovieDTO, $request->getLocale());

        if ($isMovieAdded === false) {
            throw new NotFoundHttpException('Movie not found by provided ID / TMDB ID');
        }

        return new JsonResponse(null, 202);
    }
}
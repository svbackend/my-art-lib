<?php

namespace App\Guests\Controller;

use App\Controller\BaseController;
use App\Guests\Entity\GuestSession;
use App\Movies\DTO\WatchedMovieDTO;
use App\Movies\Entity\Movie;
use App\Users\Entity\UserWatchedMovie;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\SearchService;
use App\Movies\Request\AddWatchedMovieRequest;
use App\Movies\Service\WatchedMovieService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class WatchedMovieController extends BaseController
{
    /**
     * @Route("/api/guests/{token}/watchedMovies", methods={"POST"});
     * @param $token
     * @param AddWatchedMovieRequest $addWatchedMovieRequest
     * @param Request $request
     * @param WatchedMovieService $watchedMovieService
     * @return JsonResponse
     * @throws \Exception
     */
    public function postWatchedMovies($token, AddWatchedMovieRequest $addWatchedMovieRequest, Request $request, WatchedMovieService $watchedMovieService)
    {
        $em = $this->getDoctrine()->getManager();
        $guestSessionRepository = $em->getRepository(GuestSession::class);

        /** @var $guestSession GuestSession|null */
        $guestSession = $guestSessionRepository->findOneBy([
            'token' => $token
        ]);

        if ($guestSession === null) {
            throw new NotFoundHttpException('Guest session not found by provided token');
        }

        $watchedMovieDTO = $addWatchedMovieRequest->getWatchedMovieDTO();
        $isMovieAdded = $watchedMovieService->addGuestWatchedMovie($guestSession, $watchedMovieDTO, $request->getLocale());

        if ($isMovieAdded === false) {
            throw new NotFoundHttpException('Movie not found by provided ID / TMDB ID');
        }

        return new JsonResponse(null, 202);
    }
}
<?php

namespace App\Guests\Controller;

use App\Controller\BaseController;
use App\Guests\Entity\GuestSession;
use App\Guests\Entity\GuestWatchedMovie;
use App\Guests\Repository\WatchedMovieRepository;
use App\Movies\Request\AddWatchedMovieRequest;
use App\Movies\Service\WatchedMovieService;
use App\Pagination\PaginatedCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class WatchedMovieController extends BaseController
{
    /**
     * @Route("/api/guests/{token}/watchedMovies", methods={"POST"});
     *
     * @param $token
     * @param AddWatchedMovieRequest $addWatchedMovieRequest
     * @param Request                $request
     * @param WatchedMovieService    $watchedMovieService
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function postWatchedMovies($token, AddWatchedMovieRequest $addWatchedMovieRequest, Request $request, WatchedMovieService $watchedMovieService)
    {
        $em = $this->getDoctrine()->getManager();
        $guestSessionRepository = $em->getRepository(GuestSession::class);

        /** @var $guestSession GuestSession|null */
        $guestSession = $guestSessionRepository->findOneBy([
            'token' => $token,
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

    /**
     * @Route("/api/guests/{id}/watchedMovies", methods={"GET"});
     *
     * @param Request      $request
     * @param GuestSession $guestSession
     *
     * @return JsonResponse
     */
    public function getAll(Request $request, GuestSession $guestSession)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var $watchedMovieRepository WatchedMovieRepository */
        $watchedMovieRepository = $em->getRepository(GuestWatchedMovie::class);

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $watchedMovies = new PaginatedCollection(
            $watchedMovieRepository->getAllWatchedMoviesByGuestSessionId($guestSession->getId()),
            $offset,
            $limit ? (int) $limit : null
        );

        return $this->response($watchedMovies, 200, [], [
            'groups' => ['list'],
        ]);
    }
}

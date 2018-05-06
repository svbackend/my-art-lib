<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Guests\Entity\GuestSession;
use App\Movies\DTO\WatchedMovieDTO;
use App\Movies\Entity\Movie;
use App\Pagination\PaginatedCollection;
use App\Pagination\PaginatorBuilder;
use App\Users\Entity\User;
use App\Users\Entity\UserWatchedMovie;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\SearchService;
use App\Movies\Request\AddWatchedMovieRequest;
use App\Movies\Service\WatchedMovieService;
use App\Users\Repository\WatchedMovieRepository;
use App\Users\Request\MergeWatchedMoviesRequest;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

        $watchedMovieDTO = $addWatchedMovieRequest->getWatchedMovieDTO();
        $isMovieAdded = $watchedMovieService->addUserWatchedMovie($this->getUser(), $watchedMovieDTO, $request->getLocale());

        if ($isMovieAdded === false) {
            throw new NotFoundHttpException('Movie not found by provided ID / TMDB ID');
        }

        return new JsonResponse(null, 202);
    }

    /**
     * @Route("/api/users/{id}/watchedMovies", methods={"GET"});
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function getAll(Request $request, User $user)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var $watchedMovieRepository WatchedMovieRepository */
        $watchedMovieRepository = $em->getRepository(UserWatchedMovie::class);

        $offset = (int)$request->get('offset', 0);
        $limit = $request->get('limit', null);

        $watchedMovies = new PaginatedCollection(
            $watchedMovieRepository->getAllWatchedMoviesByUserId($user->getId()),
            $offset,
            $limit ? (int)$limit : null
        );

        return $this->response($watchedMovies, 200, [], [
            'groups' => ['list']
        ]);
    }

    /**
     * @Route("/api/users/mergeWatchedMovies", methods={"POST"});
     * @param MergeWatchedMoviesRequest $mergeWatchedMoviesRequest
     * @param WatchedMovieService $watchedMovieService
     * @throws \Exception
     * @return JsonResponse
     */
    public function postMergeWatchedMovies(MergeWatchedMoviesRequest $mergeWatchedMoviesRequest, WatchedMovieService $watchedMovieService)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $guestSessionRepository = $this->getDoctrine()->getRepository(GuestSession::class);
        $guestSession = $guestSessionRepository->findOneBy([
            'token' => $mergeWatchedMoviesRequest->get('token')
        ]);

        /** @var $guestSession GuestSession|null */
        if ($guestSession === null) {
            throw new NotFoundHttpException('Guest session not found by provided token');
        }

        $watchedMovieService->mergeWatchedMovies($guestSession, $this->getUser());

        return new JsonResponse(null, 202);
    }
}
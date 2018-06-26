<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Guests\Entity\GuestSession;
use App\Movies\Repository\MovieRepository;
use App\Movies\Request\AddWatchedMovieRequest;
use App\Movies\Request\UpdateWatchedMovieRequest;
use App\Movies\Service\WatchedMovieService;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\User;
use App\Users\Entity\UserWatchedMovie;
use App\Users\Repository\WatchedMovieRepository;
use App\Users\Request\MergeWatchedMoviesRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class WatchedMovieController extends BaseController
{
    /**
     * @Route("/api/users/watchedMovies", methods={"POST"});
     *
     * @param AddWatchedMovieRequest $addWatchedMovieRequest
     * @param Request                $request
     * @param WatchedMovieService    $watchedMovieService
     *
     * @throws \Exception|NotFoundHttpException
     *
     * @return JsonResponse
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
     * @Route("/api/users/{user}/watchedMovies/{watchedMovie}", methods={"PATCH"});
     *
     * @param UserWatchedMovie          $watchedMovie
     * @param UpdateWatchedMovieRequest $request
     * @param WatchedMovieService       $watchedMovieService
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function patchWatchedMoviesById(UserWatchedMovie $watchedMovie, UpdateWatchedMovieRequest $request, WatchedMovieService $watchedMovieService)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($watchedMovie->getUser()->getId() !== $this->getUser()->getId()) {
            throw new AccessDeniedHttpException();
        }

        $watchedMovieDTO = $request->getWatchedMovieDTO();
        $watchedMovieService->updateUserWatchedMovie($watchedMovie, $watchedMovieDTO);

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(null, 200);
    }

    /**
     * @Route("/api/users/{user}/watchedMovies/movie/{movieId}", methods={"PATCH"});
     *
     * @param int                       $movieId
     * @param UpdateWatchedMovieRequest $request
     * @param WatchedMovieService       $watchedMovieService
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function patchWatchedMoviesByMovieId(int $movieId, UpdateWatchedMovieRequest $request, WatchedMovieService $watchedMovieService, WatchedMovieRepository $repository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var $user User */
        $user = $this->getUser();

        if (null === $watchedMovie = $repository->findOneByMovieId($movieId, $user->getId())) {
            throw new NotFoundHttpException();
        }

        $watchedMovieDTO = $request->getWatchedMovieDTO();
        $watchedMovieService->updateUserWatchedMovie($watchedMovie, $watchedMovieDTO);

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(null, 200);
    }

    /**
     * @Route("/api/users/{id<\d+>}/watchedMovies", methods={"GET"});
     *
     * @param Request         $request
     * @param User            $user
     * @param MovieRepository $repository
     *
     * @return JsonResponse
     */
    public function getAll(Request $request, User $user, MovieRepository $repository)
    {
        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $watchedMovies = new PaginatedCollection(
            $repository->getAllWatchedMoviesByUserId($user->getId()),
            $offset,
            $limit ? (int) $limit : null
        );

        return $this->response($watchedMovies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * @Route("/api/users/{username}/watchedMovies", methods={"GET"});
     * @ParamConverter("user", options={"mapping"={"username"="username"}})
     *
     * @param Request         $request
     * @param User            $user
     * @param MovieRepository $repository
     *
     * @return JsonResponse
     */
    public function getAllByUsername(Request $request, User $user, MovieRepository $repository)
    {
        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $watchedMovies = new PaginatedCollection(
            $repository->getAllWatchedMoviesByUserId($user->getId()),
            $offset,
            $limit ? (int) $limit : null
        );

        return $this->response($watchedMovies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * @Route("/api/users/{user}/watchedMovies/{watchedMovieId}", methods={"DELETE"});
     *
     * @param int                    $watchedMovieId
     * @param WatchedMovieRepository $repository
     *
     * @return JsonResponse
     */
    public function deleteWatchedMovies(int $watchedMovieId, WatchedMovieRepository $repository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var $currentUser User */
        $currentUser = $this->getUser();

        if (null === $watchedMovie = $repository->find($watchedMovieId)) {
            $watchedMovie = $repository->findOneByMovieId($watchedMovieId, $currentUser->getId());
        }

        if (null === $watchedMovie) {
            throw new NotFoundHttpException();
        }

        if ($watchedMovie->getUser()->getId() !== $currentUser->getId()) {
            throw new AccessDeniedHttpException();
        }

        $this->getDoctrine()->getManager()->remove($watchedMovie);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(null, 202);
    }

    /**
     * @Route("/api/users/mergeWatchedMovies", methods={"POST"});
     *
     * @param MergeWatchedMoviesRequest $mergeWatchedMoviesRequest
     * @param WatchedMovieService       $watchedMovieService
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function postMergeWatchedMovies(MergeWatchedMoviesRequest $mergeWatchedMoviesRequest, WatchedMovieService $watchedMovieService)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $guestSessionRepository = $this->getDoctrine()->getRepository(GuestSession::class);
        $guestSession = $guestSessionRepository->findOneBy([
            'token' => $mergeWatchedMoviesRequest->get('token'),
        ]);

        /** @var $guestSession GuestSession|null */
        if ($guestSession === null) {
            throw new NotFoundHttpException('Guest session not found by provided token');
        }

        $watchedMovieService->mergeWatchedMovies($guestSession, $this->getUser());

        return new JsonResponse(null, 202);
    }
}

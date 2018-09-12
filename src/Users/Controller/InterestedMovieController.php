<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Movies\Entity\Movie;
use App\Movies\Repository\MovieRepository;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\User;
use App\Users\Entity\UserInterestedMovie;
use App\Users\Repository\InterestedMovieRepository;
use App\Users\Request\AddInterestedMovieRequest;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class InterestedMovieController extends BaseController
{
    /**
     * @Route("/api/users/{id<\d+>}/interestedMovies", methods={"GET"});
     *
     * @param Request         $request
     * @param User            $user
     * @param InterestedMovieRepository $repository
     *
     * @return JsonResponse
     */
    public function getAll(Request $request, User $user, MovieRepository $repository)
    {
        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $interestedMovies = new PaginatedCollection(
            $repository->getAllInterestedMoviesByUserId($user->getId()),
            $offset,
            $limit ? (int) $limit : null,
            false
        );

        return $this->response($interestedMovies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * @Route("/api/users/interestedMovies", methods={"POST"});
     *
     * @param AddInterestedMovieRequest $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Exception
     */
    public function postInterestedMovies(AddInterestedMovieRequest $request, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var $movie Movie */
        $movie = $em->getReference(Movie::class, $request->get('movie_id'));
        $interestedMovie = new UserInterestedMovie($this->getUser(), $movie);
        $em->persist($interestedMovie);

        try {
            $em->flush();
        } catch (UniqueConstraintViolationException $exception) {
            // it's ok
        }

        return new JsonResponse(null, 202);
    }

    /**
     * @Route("/api/users/interestedMovies/{id<\d+>}", methods={"DELETE"});
     *
     * @param int $id
     * @param InterestedMovieRepository $repository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function deleteInterestedMovies(int $id, InterestedMovieRepository $repository, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var $currentUser User */
        $currentUser = $this->getUser();

        if (null === $interestedMovie = $repository->findOneById($id, $currentUser->getId())) {
            $interestedMovie = $repository->findOneByMovieId($id, $currentUser->getId());
        }

        if (null === $interestedMovie) {
            return new JsonResponse(null, 202);
        }

        $em->remove($interestedMovie);
        $em->flush();

        return new JsonResponse(null, 202);
    }
}

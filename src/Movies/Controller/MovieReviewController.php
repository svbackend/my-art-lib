<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieCard;
use App\Movies\Entity\MovieReview;
use App\Movies\Repository\MovieCardRepository;
use App\Movies\Repository\MovieReviewRepository;
use App\Movies\Request\NewMovieCardRequest;
use App\Movies\Request\NewMovieReviewRequest;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\UserRoles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MovieReviewController extends BaseController
{
    /**
     * @Route("/api/movies/{id}/reviews", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getMoviesReviews(Request $request, int $id, MovieReviewRepository $repository)
    {
        $reviews = $repository->findAllByMovie($request->getLocale(), $id);

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $collection = new PaginatedCollection($reviews, $offset, $limit);

        return $this->response($collection, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * Add new review
     *
     * @Route("/api/movies/{id}/reviews", methods={"POST"})
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return JsonResponse
     */
    public function postMoviesReviews(Request $request, NewMovieReviewRequest $reviewRequest, Movie $movie, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_USER);

        $review = $reviewRequest->get('review');
        $user = $this->getUser();

        $review = new MovieReview(
            $movie,
            $user,
            $request->getLocale(),
            $review['text']
        );

        $em->persist($review);
        $em->flush();

        return new JsonResponse();
    }

    /**
     * @Route("/api/movies/{movie_id}/reviews/{id}", methods={"DELETE"})
     */
    public function deleteMoviesReviews(MovieReview $review, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_USER);

        if ($review->getUser()->getId() !== $this->getUser()->getId()) {
            $this->denyAccessUnlessGranted([UserRoles::ROLE_MODERATOR, UserRoles::ROLE_ADMIN]);
        }

        $em->remove($review);
        $em->flush();

        return new JsonResponse();
    }
}

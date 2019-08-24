<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieCard;
use App\Movies\Repository\MovieCardRepository;
use App\Movies\Request\NewMovieCardRequest;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\UserRoles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MovieCardController extends BaseController
{
    /**
     * @Route("/api/movies/{id}/cards", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @param Request              $request
     * @param int                  $id
     * @param MovieCardRepository  $repository
     *
     * @return JsonResponse
     */
    public function getMoviesCards(Request $request, int $id, MovieCardRepository $repository)
    {
        $cards = $repository->findAllByMovie($request->getLocale(), $id);

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $collection = new PaginatedCollection($cards, $offset, $limit);

        return $this->response($collection, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * Add new recommendation.
     *
     * @Route("/api/movies/{id}/cards", methods={"POST"})
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return JsonResponse
     */
    public function postMoviesCards(Request $request, NewMovieCardRequest $cardRequest, Movie $movie, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_USER);

        $card = $cardRequest->get('card');
        $user = $this->getUser();

        $card = new MovieCard(
            $movie,
            $user,
            $request->getLocale(),
            $card['title'],
            $card['description'],
            $card['type'],
            $card['url']
        );

        $em->persist($card);
        $em->flush();

        return new JsonResponse();
    }

    /**
     * @Route("/api/movies/{movie_id}/cards/{id}", methods={"DELETE"})
     */
    public function deleteMoviesCards(MovieCard $card, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_USER);

        if ($card->getUser()->getId() !== $this->getUser()->getId()) {
            $this->denyAccessUnlessGranted([UserRoles::ROLE_MODERATOR, UserRoles::ROLE_ADMIN]);
        }

        $em->remove($card);
        $em->flush();

        return new JsonResponse();
    }
}

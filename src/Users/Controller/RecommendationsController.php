<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Movies\Repository\MovieRecommendationRepository;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\User;
use App\Users\Entity\UserRoles;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RecommendationsController extends BaseController
{
    /**
     * @Route("/api/users/{id<\d+>}/recommendations", methods={"GET"})
     *
     * @param User                          $user
     * @param Request                       $request
     * @param MovieRecommendationRepository $repository
     *
     * @return JsonResponse
     */
    public function getUserRecommendations(User $user, Request $request, MovieRecommendationRepository $repository)
    {
        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);
        $minRating = $request->get('minRating', 7);

        $currentUser = $this->getUser();

        $query = $repository->findAllByUser($user->getId(), abs((int) $minRating), $currentUser);
        $movies = new PaginatedCollection($query, $offset, $limit, false);

        return $this->response($movies, 200, [], [
            'groups' => ['list'],
        ]);
    }
}

<?php

namespace App\Actors\Controller;

use App\Controller\BaseController;
use App\Movies\Repository\MovieRepository;
use App\Pagination\PaginatedCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ActorMovieController extends BaseController
{
    /**
     * @Route("/api/actors/{id}/movies", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @param Request         $request
     * @param int             $id
     * @param MovieRepository $repository
     *
     * @return JsonResponse
     */
    public function getActorsMovies(Request $request, int $id, MovieRepository $repository)
    {
        $movies = $repository->findAllByActor($id, $this->getUser());

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $movies->setHydrationMode($movies::HYDRATE_ARRAY);
        $movies = new PaginatedCollection($movies, $offset, $limit);

        return $this->response($movies, 200, [], [
            'groups' => ['list'],
        ]);
    }
}

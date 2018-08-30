<?php

namespace App\Actors\Controller;

use App\Controller\BaseController;
use App\Movies\Repository\MovieActorRepository;
use App\Pagination\PaginatedCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ActorMovieController extends BaseController
{
    /**
     * @Route("/api/actors/{id}/movies", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @param Request              $request
     * @param int                  $id
     * @param MovieActorRepository $repository
     *
     * @return JsonResponse
     */
    public function getMoviesActors(Request $request, int $id, MovieActorRepository $repository)
    {
        $actors = $repository->findAllByActor($id);

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $collection = new PaginatedCollection($actors, $offset, $limit);

        return $this->response($collection, 200, [], [
            'groups' => ['list'],
        ]);
    }
}

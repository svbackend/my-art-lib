<?php

namespace App\Actors\Controller;

use App\Controller\BaseController;
use App\Movies\Repository\MovieRepository;
use App\Movies\Transformer\MovieTransformer;
use App\Pagination\CustomPaginatedCollection;
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
     * @throws
     *
     * @return JsonResponse
     */
    public function getActorsMovies(Request $request, int $id, MovieRepository $repository)
    {
        [$movies, $ids, $count] = $repository->findAllByActor($id, $this->getUser());

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $collection = new CustomPaginatedCollection($movies, $ids, $count, $offset, $limit);

        return $this->items($collection, MovieTransformer::list());
    }
}

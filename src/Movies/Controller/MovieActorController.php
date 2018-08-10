<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Movies\Repository\MovieActorRepository;
use App\Movies\Service\TmdbSearchService;
use App\Pagination\PaginatedCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MovieActorController extends BaseController
{
    /**
     * @Route("/api/movies/{id}/actors", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @param Request              $request
     * @param int                  $id
     * @param MovieActorRepository $repository
     *
     * @return JsonResponse
     */
    public function getMoviesActors(Request $request, int $id, MovieActorRepository $repository)
    {
        $actors = $repository->findAllByMovie($id);

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $collection = new PaginatedCollection($actors, $offset, $limit);

        return $this->response($collection, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * @Route("/api/test", methods={"GET"})
     */
    public function test(TmdbSearchService $service)
    {
        return new JsonResponse($service->findMovieById(146));
    }
}

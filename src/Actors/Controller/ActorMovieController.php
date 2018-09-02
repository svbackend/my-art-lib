<?php

namespace App\Actors\Controller;

use App\Controller\BaseController;
use App\Movies\Entity\MovieActor;
use App\Movies\Pagination\MovieCollection;
use App\Movies\Repository\MovieActorRepository;
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
     * @param Request              $request
     * @param int                  $id
     * @param MovieActorRepository $repository
     * @param MovieRepository      $movieRepository
     *
     * @return JsonResponse
     */
    public function getActorsMovies(Request $request, int $id, MovieActorRepository $repository, MovieRepository $movieRepository)
    {
        $movieActors = $repository->findAllByActor($id, $this->getUser())->getResult();

        $offset = (int) $request->get('offset', 0);

        $collection = new MovieCollection(array_map(function (MovieActor $movieActor) {
            return $movieActor->getMovie();
        }, $movieActors), count($movieActors), $offset);

        return $this->response($collection, 200, [], [
            'groups' => ['list'],
        ]);
    }
}

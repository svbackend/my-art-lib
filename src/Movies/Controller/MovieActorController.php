<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Movies\Repository\MovieActorRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class MovieActorController extends BaseController
{
    /**
     * @Route("/api/movies/{id}/actors", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @param int                  $id
     * @param MovieActorRepository $repository
     *
     * @return JsonResponse
     */
    public function getMoviesActors(int $id, MovieActorRepository $repository)
    {
        return $this->response($repository->findAllByMovie($id), 200, [], [
            'groups' => ['list'],
        ]);
    }
}

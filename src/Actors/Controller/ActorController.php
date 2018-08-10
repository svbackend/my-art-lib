<?php

namespace App\Actors\Controller;

use App\Actors\Entity\Actor;
use App\Actors\Entity\ActorTranslations;
use App\Actors\Repository\ActorRepository;
use App\Actors\Request\UpdateActorRequest;
use App\Controller\BaseController;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\UserRoles;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ActorController extends BaseController
{
    /**
     * @Route("/api/actors", methods={"GET"})
     *
     * @param Request         $request
     * @param ActorRepository $repository
     *
     * @return JsonResponse
     */
    public function getAll(Request $request, ActorRepository $repository)
    {
        $actors = $repository->findAllWithTranslations();

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $movies = new PaginatedCollection($actors, $offset, $limit);

        return $this->response($movies, 200, [], [
            'groups' => ['list'],
        ]);
    }

    /**
     * @Route("/api/actors/{id}", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @param Actor $actor
     *
     * @return JsonResponse
     */
    public function getActors(Actor $actor)
    {
        return $this->response($actor, 200, [], [
            'groups' => ['view'],
        ]);
    }

    /**
     * @Route("/api/actors/{id}", methods={"POST", "PUT", "PATCH"}, requirements={"id"="\d+"})
     *
     * @param Actor              $actor
     * @param UpdateActorRequest $request
     *
     * @throws \ErrorException
     *
     * @return JsonResponse
     */
    public function putActors(Actor $actor, UpdateActorRequest $request)
    {
        $this->denyAccessUnlessGranted(UserRoles::ROLE_ADMIN);

        $actorData = $request->get('actor');
        $actorTranslationData = $actorData['translations'];

        $actor->setOriginalName($actorData['originalName']);
        $actor->setImdbId($actorData['imdbId']);
        $actor->setGender($actorData['gender']);
        $actor->setBirthday(new \DateTimeImmutable($actorData['birthday']));

        $addTranslation = function (array $trans) use ($actor) {
            $actorTranslation = new ActorTranslations($actor, $trans['locale'], $trans['name']);
            $actorTranslation->setBiography($trans['biography']);
            $actorTranslation->setPlaceOfBirth($trans['placeOfBirth']);
            $actor->addTranslation($actorTranslation);
        };

        $updateTranslation = function (array $trans, ActorTranslations $oldTranslation) use ($actor) {
            $oldTranslation->setName($trans['name']);
            $oldTranslation->setBiography($trans['biography']);
            $oldTranslation->setPlaceOfBirth($trans['placeOfBirth']);
        };

        $actor->updateTranslations($actorTranslationData, $addTranslation, $updateTranslation);

        $em = $this->getDoctrine()->getManager();
        $em->persist($actor); // if there 1+ new translations lets persist movie to be sure that they will be saved
        $em->flush();

        return new JsonResponse(null, 202);
    }
}

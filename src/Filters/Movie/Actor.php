<?php

namespace App\Filters\Movie;

use App\Filters\Filter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * /api/movies?g[]=2&g[]=1&gt=AND => Will display all movies in genres with ids 1 & 2 (In both)
 * /api/movies?g[]=2&g[]=1 => Will display all movies in genres with ids 1 & 2 (At least in one of them)
 */
class Actor implements Filter
{
    private const CONDITION_TYPE_OR = 'OR';
    private const CONDITION_TYPE_AND = 'AND';

    public function handle(ParameterBag $params, QueryBuilder $qb): QueryBuilder
    {
        $conditionType = $params->get('at', self::CONDITION_TYPE_OR);
        $actors = $params->get('a', []);

        if (count($actors) === 0) {
            return $qb;
        }

        array_walk($actors, static function ($id) { return (int)$id; });

        if ($conditionType === self::CONDITION_TYPE_OR || count($actors) === 1) {
            return $qb
                ->leftJoin('m.actors', 'ma')
                ->andWhere('ma.actor IN (:filter_actors)')
                ->setParameter('filter_actors', $actors);
        }

        $moviesIds = $this->getIntersectIds($qb, $actors);

        if (count($moviesIds) === 0) {
            return $qb->andWhere('1=0');
        }

        return $qb->andWhere($qb->expr()->in('m.id', $moviesIds));
    }

    private function getIntersectIds(QueryBuilder $qb, array $actors): array
    {
        $newQb = clone $qb;
        $query = $newQb
            ->select('m.id')
            ->leftJoin('m.actors', 'ma')
            ->andWhere('ma.actor = :filter_actor_id')
            ->getQuery()
        ;

        $ids = [];
        foreach ($actors as $actorId) {
            $ids[] = array_map(
                static function($item) { return $item['id']; },
                $query
                    ->setParameter('filter_actor_id', $actorId)
                    ->getArrayResult()
            );
        }

        return array_intersect(...$ids);
    }
}
<?php

namespace App\Filters\Movie;

use App\Filters\Filter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

class Genre implements Filter
{
    private const CONDITION_TYPE_OR = 'OR';
    private const CONDITION_TYPE_AND = 'AND';

    public function handle(ParameterBag $params, QueryBuilder $qb): QueryBuilder
    {
        $conditionType = $params->get('gt', self::CONDITION_TYPE_OR);
        $genres = $params->get('g', []);

        if (count($genres) === 0) {
            return $qb;
        }

        array_walk($genres, static function ($id) { return (int)$id; });

        if ($conditionType === self::CONDITION_TYPE_OR) {
            return $qb->andWhere('m.genre.id IN (:filter_genres)')->setParameter('filter_genres', $genres);
        }

        // todo how to implement CONDITION_TYPE_AND ?
        return $qb->andWhere('m.genre.id IN (:filter_genres)')->setParameter('filter_genres', $genres);
    }
}
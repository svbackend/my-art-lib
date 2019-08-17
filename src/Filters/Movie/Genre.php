<?php

namespace App\Filters\Movie;

use App\Filters\Filter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * /api/movies?g[]=2&g[]=1&gt=AND => Will display all movies in genres with ids 1 & 2 (In both)
 * /api/movies?g[]=2&g[]=1 => Will display all movies in genres with ids 1 & 2 (At least in one of them).
 */
class Genre implements Filter
{
    private const CONDITION_TYPE_OR = 'OR';
    private const CONDITION_TYPE_AND = 'AND';

    public function handle(ParameterBag $params, QueryBuilder $qb): QueryBuilder
    {
        $conditionType = $params->get('gt', self::CONDITION_TYPE_OR);
        $genres = $params->get('g', []);

        if (\count($genres) === 0) {
            return $qb;
        }

        array_walk($genres, static function ($id) { return (int) $id; });

        if ($conditionType === self::CONDITION_TYPE_OR || \count($genres) === 1) {
            return $qb
                ->leftJoin('m.genres', 'mg')
                ->andWhere('mg.id IN (:filter_genres)')
                ->setParameter('filter_genres', $genres);
        }

        $moviesIds = $this->getIntersectIds($qb, $genres);

        if (\count($moviesIds) === 0) {
            return $qb->andWhere('1=0');
        }

        return $qb->andWhere($qb->expr()->in('m.id', $moviesIds));
    }

    private function getIntersectIds(QueryBuilder $qb, array $genres): array
    {
        $newQb = clone $qb;
        $query = $newQb
            ->select('m.id')
            ->leftJoin('m.genres', 'mg')
            ->andWhere('mg.id = :filter_genre_id')
            ->getQuery()
        ;

        $ids = [];
        foreach ($genres as $genreId) {
            $ids[] = array_map(
                static function ($item) { return $item['id']; },
                $query
                    ->setParameter('filter_genre_id', $genreId)
                    ->getArrayResult()
            );
        }

        return array_intersect(...$ids);
    }
}

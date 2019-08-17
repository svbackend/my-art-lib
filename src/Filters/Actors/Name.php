<?php

namespace App\Filters\Actor;

use App\Filters\Filter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * /api/actors/search?n=Jake
 */
class Name implements Filter
{
    public function handle(ParameterBag $params, QueryBuilder $qb): QueryBuilder
    {
        $name = $params->get('n', '');

        if ($this->isValid($name) === false) {
            return $qb;
        }

        $qb->andWhere('(a.originalName LIKE :filter_name) OR (at.name LIKE :filter_name)')
            ->setParameter('filter_name', "%{$name}%");

        return $qb;
    }

    private function isValid(string $name): bool
    {
        return strlen($name) >= 3;
    }
}
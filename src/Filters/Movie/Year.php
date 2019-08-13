<?php

namespace App\Filters\Movie;

use App\Filters\Filter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

class Year implements Filter
{
    private const VALID_OPERATIONS = ['=', '>', '<', '>=', '<='];

    public function handle(ParameterBag $params, QueryBuilder $qb): QueryBuilder
    {
        if (null === $year = $params->get('y')) {
            return $qb;
        }

        if (is_array($year) === false) {
            $year = ['=', $year];
        }

        [$operation, $value] = $year;

        if ($this->isValidOperation($operation) === false) {
            return $qb;
        }

        return $qb
            ->andWhere("m.year {$operation} :y")
            ->setParameter('y', $value);
    }

    private function isValidOperation(string $operation): bool
    {
        return in_array($operation, self::VALID_OPERATIONS, true);
    }
}
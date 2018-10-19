<?php

declare(strict_types=1);

namespace App\Pagination;

use Doctrine\ORM\Query;

class CustomPaginatedCollection implements PaginatedCollectionInterface
{
    private $offset = 0;
    private $limit = 20;
    private $items;
    private $itemsCount;

    public function __construct(Query $itemsQuery, Query $idsQuery, Query $countQuery, int $offset, ?int $limit = null)
    {
        $this->offset = abs($offset);

        if ($limit !== null) {
            $this->limit = abs($limit);
        }

        $idsQuery = $idsQuery->setFirstResult($this->offset)->setMaxResults($this->limit);
        $ids = $this->getIds($idsQuery);
        $this->itemsCount = $countQuery->getSingleScalarResult();
        $itemsQuery->setParameter('ids', $ids);
        $this->items = $itemsQuery->getArrayResult();
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return (int)$this->itemsCount;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    private function getIds(Query $idsQuery): array
    {
        $ids = $idsQuery->getArrayResult();

        if (is_array(reset($ids)) === true) {
            return array_map(function ($id) {
                return reset($id);
            }, $ids);
        }

        return $ids;
    }
}

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
    private $itemsIds;

    /**
     * CustomPaginatedCollection constructor.
     *
     * @param Query    $itemsQuery
     * @param Query    $idsQuery
     * @param Query    $countQuery
     * @param int      $offset
     * @param int|null $limit
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __construct(Query $itemsQuery, Query $idsQuery, Query $countQuery, int $offset, ?int $limit = null)
    {
        $this->offset = abs($offset);

        if ($limit !== null) {
            $this->limit = abs($limit);
        }

        $idsQuery = $idsQuery->setFirstResult($this->offset)->setMaxResults($this->limit);
        $this->itemsIds = $idsQuery->getArrayResult();
        $this->itemsCount = $countQuery->getSingleScalarResult();
        $ids = $this->getIds($this->itemsIds);
        $itemsQuery->setParameter('ids', $ids);
        $this->items = $this->sortItems($itemsQuery->getArrayResult(), $ids);
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getItemsIds(): array
    {
        return $this->itemsIds;
    }

    public function getTotal(): int
    {
        return (int) $this->itemsCount;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    private function sortItems(array $items, array $ids): array
    {
        if (\count($ids) === 0) {
            return [];
        }

        $ids = array_flip($ids);
        usort($items, function (array $item1, array $item2) use ($ids) {
            return $ids[$item1['id']] <=> $ids[$item2['id']];
        });

        return $items;
    }

    private function getIds(array $ids): array
    {
        if (\count($ids) === 0) {
            return [];
        }

        $firstValue = reset($ids);
        if (\is_array($firstValue) === true) {
            return array_map(function ($id) {
                return reset($id);
            }, $ids);
        }

        return $ids;
    }
}

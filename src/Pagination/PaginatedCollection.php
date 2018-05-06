<?php
declare(strict_types=1);

namespace App\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginatedCollection
{
    private $offset = 0;
    private $limit = 30;
    private $paginator;

    public function __construct(Query $query, int $offset = 0, ?int $limit, bool $fetchJoinCollection = true)
    {
        $this->offset = abs($offset);

        if ($limit !== null) $this->limit = abs($limit);

        $query = $query->setFirstResult($this->offset)->setMaxResults($this->limit);
        $this->paginator = new Paginator($query, $fetchJoinCollection);
    }

    public function getItems(): Paginator
    {
        return $this->paginator;
    }

    public function getTotal(): int
    {
        return (int)$this->getItems()->count();
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
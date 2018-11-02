<?php

declare(strict_types=1);

namespace App\Movies\Repository;

use App\Movies\Entity\Movie;
use App\Movies\Entity\ReleaseDateQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ReleaseDateQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReleaseDateQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReleaseDateQueue[]    findAll()
 * @method ReleaseDateQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReleaseDateQueueRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReleaseDateQueue::class);
    }

    public function findAllWithMovies(int $isActive = 1): Query
    {
        $query = $this->createQueryBuilder('rdq')
            ->leftJoin('rdq.movie', 'm')
            ->addSelect('m')
            ->where('rdq.isActive = :active')
            ->setParameter('active', $isActive)
            ->getQuery();

        return $query;
    }
}

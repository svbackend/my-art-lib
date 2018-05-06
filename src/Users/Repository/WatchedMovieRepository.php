<?php
declare(strict_types=1);

namespace App\Users\Repository;

use App\Users\Entity\User;
use App\Users\Entity\UserWatchedMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method UserWatchedMovie|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserWatchedMovie|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserWatchedMovie[]    findAll()
 * @method UserWatchedMovie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WatchedMovieRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserWatchedMovie::class);
    }

    public function getAllWatchedMoviesByUserId(int $userId): Query
    {
        return $this->createQueryBuilder('wm')
            ->where('wm.user = :userId')
            ->setParameter('userId', $userId)
            ->addOrderBy('wm.id', 'DESC')
            ->getQuery();
    }
}

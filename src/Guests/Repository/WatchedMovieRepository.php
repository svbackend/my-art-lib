<?php

declare(strict_types=1);

namespace App\Guests\Repository;

use App\Guests\Entity\GuestWatchedMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method GuestWatchedMovie|null find($id, $lockMode = null, $lockVersion = null)
 * @method GuestWatchedMovie|null findOneBy(array $criteria, array $orderBy = null)
 * @method GuestWatchedMovie[]    findAll()
 * @method GuestWatchedMovie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WatchedMovieRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GuestWatchedMovie::class);
    }

    public function getAllWatchedMoviesByGuestSessionId(int $guestSessionId): Query
    {
        return $this->createQueryBuilder('wm')
            ->leftJoin('wm.movie', 'm')
            ->addSelect('m')
            ->leftJoin('m.translations', 'mt')
            ->addSelect('mt')
            ->leftJoin('m.genres', 'mg')
            ->addSelect('mg')
            ->leftJoin('mg.translations', 'mgt')
            ->addSelect('mgt')
            ->where('wm.guestSession = :guestSessionId')
            ->setParameter('guestSessionId', $guestSessionId)
            ->addOrderBy('wm.id', 'DESC')
            ->getQuery();
    }
}

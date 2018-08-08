<?php

declare(strict_types=1);

namespace App\Actors\Repository;

use App\Actors\Entity\Actor;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieRecommendation;
use App\Users\Entity\User;
use App\Users\Entity\UserWatchedMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Actor|null find($id, $lockMode = null, $lockVersion = null)
 * @method Actor|null findOneBy(array $criteria, array $orderBy = null)
 * @method Actor[]    findAll()
 * @method Actor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActorRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Actor::class);
    }

    public function findByTmdbId(int $tmdbId): ?Actor
    {
        return $this->createQueryBuilder('a')
            ->where('a.tmdb.id = :id')
            ->setParameter('id', $tmdbId)
            ->getQuery()
            ->getResult();

        return $result;
    }
}

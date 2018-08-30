<?php

declare(strict_types=1);

namespace App\Movies\Repository;

use App\Movies\Entity\MovieActor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MovieActor|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovieActor|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovieActor[]    findAll()
 * @method MovieActor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieActorRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MovieActor::class);
    }

    public function findAllByMovie(int $movieId): Query
    {
        return $this->createQueryBuilder('ma')
            ->leftJoin('ma.actor', 'a')
            ->addSelect('a')
            ->where('ma.movie = :movieId')
            ->setParameter('movieId', $movieId)
            ->getQuery();
    }

    public function findAllByActor(int $actorId): Query
    {
        return $this->createQueryBuilder('ma')
            ->leftJoin('ma.movie', 'm')
            ->addSelect('m')
            ->where('ma.actor = :actorId')
            ->setParameter('actorId', $actorId)
            ->getQuery();
    }
}

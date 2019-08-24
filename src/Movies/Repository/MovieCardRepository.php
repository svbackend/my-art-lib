<?php

namespace App\Movies\Repository;

use App\Movies\Entity\MovieCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MovieCard|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovieCard|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovieCard[]    findAll()
 * @method MovieCard[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieCardRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MovieCard::class);
    }

    public function findAllByMovie(string $locale, int $movieId): Query
    {
        return $this->createQueryBuilder('mc')
            ->where('mc.movie = :movieId AND mc.locale = :locale')
            ->setParameter('movieId', $movieId)
            ->setParameter('locale', $locale)
            ->getQuery();
    }
}

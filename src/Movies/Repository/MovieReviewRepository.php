<?php

namespace App\Movies\Repository;

use App\Movies\Entity\MovieReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Users\Entity\UserWatchedMovie;

/**
 * @method MovieReview|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovieReview|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovieReview[]    findAll()
 * @method MovieReview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieReviewRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MovieReview::class);
    }

    public function findAllByMovie(string $locale, int $movieId): Query
    {
        return $this->createQueryBuilder('review')
            ->leftJoin('review.userWatchedMovie', 'uwm')
            ->addSelect('uwm')
            ->where('review.movie = :movieId AND review.locale = :locale')
            ->setParameter('movieId', $movieId)
            ->setParameter('locale', $locale)
            ->getQuery();
    }
}

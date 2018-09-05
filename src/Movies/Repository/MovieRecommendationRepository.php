<?php

declare(strict_types=1);

namespace App\Movies\Repository;

use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieRecommendation;
use App\Users\Entity\User;
use App\Users\Entity\UserWatchedMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRecommendationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MovieRecommendation::class);
    }

    public function findAllByUser(int $userId, int $minVote = 7): Query
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('m, COUNT(mr.recommendedMovie) HIDDEN rate')
            ->from(UserWatchedMovie::class, 'uwm')
            ->leftJoin(MovieRecommendation::class, 'mr', 'WITH', 'uwm.movie = mr.originalMovie')
            ->leftJoin(Movie::class, 'm', 'WITH', 'mr.recommendedMovie = m')
            ->leftJoin(UserWatchedMovie::class, 'uwmj', 'WITH', 'uwmj.movie = mr.recommendedMovie AND uwmj.user = :user')
            ->where('uwmj.id IS NULL AND uwm.user = :user AND uwm.vote >= :vote')
            ->setParameter('user', $userId)
            ->setParameter('vote', $minVote)
            ->groupBy('mr.recommendedMovie, m.id')
            ->orderBy('rate', 'DESC')
            ->addOrderBy('MAX(mr.id)', 'DESC')
            ->getQuery();

        return $query;
    }

    public function findAllByMovieAndUser(int $movieId, int $userId): Query
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('m, COUNT(mr.recommendedMovie) HIDDEN rate')
            ->from(MovieRecommendation::class, 'mr')
            ->leftJoin(Movie::class, 'm', 'WITH', 'mr.recommendedMovie = m')
            ->leftJoin('m.userRecommendedMovie', 'urm', 'WITH', 'urm.user = :user AND urm.recommendedMovie = mr.recommendedMovie')
            ->addSelect('urm')
            ->where('mr.originalMovie = :movie')
            ->setParameter('user', $userId)
            ->setParameter('movie', $movieId)
            ->groupBy('mr.recommendedMovie, m.id, urm.id')
            ->orderBy('rate', 'DESC')
            ->addOrderBy('MAX(mr.id)', 'DESC')
            ->getQuery();

        return $query;
    }

    public function findAllByMovie(int $movieId): Query
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('m, COUNT(mr.recommendedMovie) HIDDEN rate')
            ->from(MovieRecommendation::class, 'mr')
            ->leftJoin(Movie::class, 'm', 'WITH', 'mr.recommendedMovie = m')
            //->leftJoin('m.userRecommendedMovie', 'urm', 'WITH', 'urm.user = :user AND urm.recommendedMovie = mr.recommendedMovie')
            //->addSelect('urm')
            ->where('mr.originalMovie = :movie')
            //->setParameter('user', $userId)
            ->setParameter('movie', $movieId)
            ->groupBy('mr.recommendedMovie, m.id')
            ->orderBy('rate', 'DESC')
            ->addOrderBy('MAX(mr.id)', 'DESC')
            ->getQuery();

        return $query;
    }
}

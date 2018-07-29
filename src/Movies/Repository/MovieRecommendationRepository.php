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
            ->where('uwm.user = :user AND uwm.vote >= :vote')
            ->setParameter('user', $userId)
            ->setParameter('vote', $minVote)
            ->groupBy('mr.recommendedMovie, m.id')
            ->orderBy('rate', 'DESC')
            ->getQuery();

        return $query;
    }

    /**
     * @param int $movieId
     * @param int $userId
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array [...[movie_id: int, rate: int, user_id: ?int]}
     */
    public function findAllByMovieAndUser(int $movieId, int $userId): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = 'SELECT DISTINCT ON(mr.recommended_movie_id) mr.recommended_movie_id movie_id, mr.rate, umr.user_id
            FROM (
                SELECT mr.recommended_movie_id, COUNT(mr.recommended_movie_id) rate
                FROM movies_recommendations mr
                WHERE mr.original_movie_id = :movie_id
                GROUP BY mr.recommended_movie_id
                ORDER BY rate
            ) mr 
			LEFT JOIN movies_recommendations umr ON umr.recommended_movie_id = mr.recommended_movie_id AND umr.user_id = :user_id';

        $statement = $connection->prepare($sql);
        $statement->bindValue('movie_id', $movieId);
        $statement->bindValue('user_id', $userId);

        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @param int $movieId
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array [...[movie_id: int, rate: int]}
     */
    public function findAllByMovie(int $movieId): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = 'SELECT DISTINCT ON(mr.recommended_movie_id) mr.recommended_movie_id movie_id, mr.rate
            FROM (
                SELECT mr.recommended_movie_id, COUNT(mr.recommended_movie_id) rate
                FROM movies_recommendations mr
                WHERE mr.original_movie_id = :movie_id
                GROUP BY mr.recommended_movie_id
                ORDER BY rate
            ) mr
            ';

        $statement = $connection->prepare($sql);
        $statement->bindValue('movie_id', $movieId);

        $statement->execute();

        return $statement->fetchAll();
    }
}

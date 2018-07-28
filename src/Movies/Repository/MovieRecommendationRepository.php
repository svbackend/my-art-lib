<?php

declare(strict_types=1);

namespace App\Movies\Repository;

use App\Guests\Entity\GuestSession;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieRecommendation;
use App\Users\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
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

    public function findAllByMovieAndUser(int $movieId, int $userId)
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = 'SELECT DISTINCT ON(mr.recommended_movie_id) mr.recommended_movie_id, mr.rate, umr.user_id, movies.*, mt.*, uwm.*
            FROM (
                SELECT mr.recommended_movie_id, COUNT(mr.recommended_movie_id) rate
                FROM movies_recommendations mr
                WHERE mr.original_movie_id = :movie_id
                GROUP BY mr.recommended_movie_id
                ORDER BY rate
            ) mr 
			LEFT JOIN movies_recommendations umr ON umr.recommended_movie_id = mr.recommended_movie_id AND umr.user_id = :user_id
			LEFT JOIN movies ON movies.id = mr.recommended_movie_id
			LEFT JOIN movies_translations mt ON movies.id = mt.movie_id
			LEFT JOIN users_watched_movies uwm ON uwm.movie_id = mr.recommended_movie_id AND uwm.user_id = :user_id';

        $statement = $connection->prepare($sql);
        $statement->bindValue('movie_id', $movieId);
        $statement->bindValue('user_id', $userId);

        $statement->execute();

        return $statement->fetchAll();
    }
}

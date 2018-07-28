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

    public function findAllByMovie(int $movieId)
    {
        // todo
        /*
            SELECT DISTINCT ON(mr.recommended_movie_id) mrj.id, mr.recommended_movie_id, mr.rate
            FROM (
                SELECT mr.recommended_movie_id, COUNT(mr.recommended_movie_id) rate
                FROM movies_recommendations mr
                WHERE mr.original_movie_id = 16718
                GROUP BY mr.recommended_movie_id
            ) mr
            JOIN movies_recommendations mrj ON mr.recommended_movie_id = mrj.recommended_movie_id
            GROUP BY mrj.id, mr.recommended_movie_id, mr.rate
         */
    }
}

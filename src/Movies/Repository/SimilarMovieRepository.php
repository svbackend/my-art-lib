<?php

declare(strict_types=1);

namespace App\Movies\Repository;

use App\Movies\Entity\Movie;
use App\Movies\Entity\SimilarMovie;
use App\Users\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SimilarMovie|null find($id, $lockMode = null, $lockVersion = null)
 * @method SimilarMovie|null findOneBy(array $criteria, array $orderBy = null)
 * @method SimilarMovie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SimilarMovieRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SimilarMovie::class);
    }
}

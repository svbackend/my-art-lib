<?php

declare(strict_types=1);

namespace App\Users\Repository;

use App\Users\Entity\UserInterestedMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method UserInterestedMovie|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserInterestedMovie|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserInterestedMovie[]    findAll()
 * @method UserInterestedMovie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterestedMovieRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserInterestedMovie::class);
    }

    public function findOneByMovieId(int $movieId, int $userId): ?UserInterestedMovie
    {
        return $this->findOneBy([
            'movie' => $movieId,
            'user' => $userId,
        ]);
    }
}

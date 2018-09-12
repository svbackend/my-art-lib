<?php

declare(strict_types=1);

namespace App\Users\Repository;

use App\Movies\Entity\Movie;
use App\Users\Entity\UserInterestedMovie;
use App\Users\Entity\UserWatchedMovie;
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

    public function findAllByUser(int $userId)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(UserInterestedMovie::class, 'im')
            ->leftJoin(Movie::class, 'm', 'WITH', 'm.id = im.movie')
            ->leftJoin('m.translations', 'mt')
            ->addSelect('mt')
            ->leftJoin('m.genres', 'mg')
            ->addSelect('mg')
            ->leftJoin('mg.translations', 'mgt')
            ->addSelect('mgt')
            ->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'm.id = uwm.movie AND uwm.user = :user')
            ->addSelect('uwm')
            ->leftJoin('m.userInterestedMovie', 'uim', 'WITH', 'm.id = uim.movie AND uim.user = :user')
            ->addSelect('uim')
            ->where('im.user = :user')
            ->setParameter('user', $userId)
            ->getQuery();
    }

    public function findOneById(int $interestedMovieId, int $userId): ?UserInterestedMovie
    {
        return $this->findOneBy([
            'id' => $interestedMovieId,
            'user' => $userId,
        ]);
    }

    public function findOneByMovieId(int $movieId, int $userId): ?UserInterestedMovie
    {
        return $this->findOneBy([
            'movie' => $movieId,
            'user' => $userId,
        ]);
    }
}

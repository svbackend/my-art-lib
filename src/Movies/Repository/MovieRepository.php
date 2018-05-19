<?php
declare(strict_types=1);

namespace App\Movies\Repository;

use App\Movies\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * TODO add relations to all queries
 *
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function findAllWithIsWatchedFlag(int $userId)
    {
        $result = $this->createQueryBuilder('m')
            ->leftJoin('m.translations', 'mt')
            ->addSelect('mt')
            ->leftJoin('m.genres', 'mg')
            ->addSelect('mg')
            ->leftJoin('mg.translations', 'mgt')
            ->addSelect('mgt')
            ->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'uwm.user = :user_id') // if this relation exists then user has already watched this movie
            ->addSelect('uwm')
            ->setParameter('user_id', $userId)
            ->getQuery();

        return $result;
    }

    public function findAllQuery()
    {
        $result = $this->createQueryBuilder('m')
            ->leftJoin('m.translations', 'mt')
            ->addSelect('mt')
            ->leftJoin('m.genres', 'mg')
            ->addSelect('mg')
            ->leftJoin('mg.translations', 'mgt')
            ->addSelect('mgt')
            ->getQuery();

        return $result;
    }

    // todo optimization (attach relations)
    public function findByTitleQuery(string $query)
    {
        $query = mb_strtolower($query);
        $result = $this->createQueryBuilder('m')
            ->leftJoin('m.translations', 't')
            ->andWhere('LOWER(m.originalTitle) LIKE :title OR LOWER(t.title) LIKE :title')
            ->setParameter('title', "%{$query}%")
            ->getQuery();

        return $result;
    }

    /**
     * This method will return array of already existed tmdb ids in our database
     *
     * @param array $tmdb_ids
     * @return array
     */
    public function getExistedTmdbIds(array $tmdb_ids)
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.tmdb.id')
            ->where('m.tmdb.id IN (:ids)')
            ->setParameter('ids', $tmdb_ids)
            ->getQuery()->getArrayResult();

        return $result;
    }

    public function findOneByIdOrTmdbId(?int $id = null, ?int $tmdb_id = null)
    {
        if ($id === null && $tmdb_id === null) {
            throw new \InvalidArgumentException('Movie ID or TMDB ID should be provided');
        }

        return $id ? $this->find($id) : $this->findOneBy(['tmdb.id' => $tmdb_id]);
    }
}

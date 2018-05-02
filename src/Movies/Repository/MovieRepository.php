<?php
declare(strict_types=1);

namespace App\Movies\Repository;

use App\Movies\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
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

    public function findByTitle(string $query)
    {
        $query = mb_strtolower($query);
        $result = $this->createQueryBuilder('m')
            ->leftJoin('m.translations', 't')
            ->andWhere('LOWER(m.originalTitle) LIKE :title OR LOWER(t.title) LIKE :title')
            ->setParameter('title', "%{$query}%")
            ->getQuery()->getResult();

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
}

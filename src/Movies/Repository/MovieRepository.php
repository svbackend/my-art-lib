<?php
declare(strict_types=1);

namespace App\Movies\Repository;

use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTranslations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\ExpressionBuilder;
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

    public function search(string $query)
    {
        $query = mb_strtolower($query);
        $result = $this->createQueryBuilder('m')
            ->leftJoin('m.translations', 't')
            ->andWhere('LOWER(m.originalTitle) LIKE :title OR LOWER(t.title) LIKE :title')
            ->setParameter('title', "%{$query}%")
            ->getQuery()->getResult();

        return $result;
    }

    public function getTmdbIds(array $ids)
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.tmdb.id')
            ->where('m.tmdb.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()->getArrayResult();

        return $result;
    }
}

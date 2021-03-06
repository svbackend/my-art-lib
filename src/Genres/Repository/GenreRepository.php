<?php

declare(strict_types=1);

namespace App\Genres\Repository;

use App\Genres\Entity\Genre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Genre|null find($id, $lockMode = null, $lockVersion = null)
 * @method Genre|null findOneBy(array $criteria, array $orderBy = null)
 * @method Genre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GenreRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Genre::class);
    }

    public function findByTmdbIds(array $ids)
    {
        return $this->createQueryBuilder('g')
            ->where('g.tmdbId IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function findAllWithTranslations()
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.translations', 'gt')
            ->addSelect('gt')
            ->orderBy('gt.name');
    }
}

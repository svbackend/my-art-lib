<?php

declare(strict_types=1);

namespace App\Movies\Repository;

use App\Countries\Entity\Country;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieReleaseDate;
use App\Users\Entity\User;
use App\Users\Entity\UserInterestedMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MovieReleaseDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovieReleaseDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovieReleaseDate[]    findAll()
 * @method MovieReleaseDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieReleaseDateRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MovieReleaseDate::class);
    }

    public function findOneByCountry(int $movieId, int $countryId): ?MovieReleaseDate
    {
        return $this->findOneBy([
            'movie' => $movieId,
            'country' => $countryId,
        ]);
    }

    public function findAllByDate(string $date): Query
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb = $qb->select('u')
            ->from(UserInterestedMovie::class, 'uim')
            ->leftJoin(User::class, 'u', 'WITH', 'uim.user = u')
            ->leftJoin('u.profile', 'up')
            ->leftJoin(Country::class, 'c', 'WITH', 'up.country_code = c.code')
            ->addSelect('c')
            ->leftJoin(MovieReleaseDate::class, 'mrd', 'WITH', 'mrd.country = c AND mrd.movie = uim.movie AND mrd.date = :date AND mrd.id IS NOT NULL')
            ->setParameter('date', $date)
            ->leftJoin(Movie::class, 'm', 'WITH', 'm = uim.movie')
            ->addSelect('m')
            ->where('mrd.id IS NOT NULL')
            ->getQuery();

        return $qb;
    }
}

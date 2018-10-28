<?php

declare(strict_types=1);

namespace App\Movies\Repository;

use App\Countries\Entity\Country;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieReleaseDate;
use App\Movies\Entity\ReleaseDateQueue;
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

    public function findOneByCountry(int $movieId, Country $country): ?MovieReleaseDate
    {
        return $this->findOneBy([
            'movie' => $movieId,
            'country' => $country->getCode(),
        ]);
    }
}

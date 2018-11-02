<?php

declare(strict_types=1);

namespace App\Countries\Repository;

use App\Countries\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Country|null find($id, $lockMode = null, $lockVersion = null)
 * @method Country|null findOneBy(array $criteria, array $orderBy = null)
 * @method Country[]    findAll()
 * @method Country[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Country::class);
    }

    public function findOneByCode(string $code): ?Country
    {
        return $this->findOneBy([
            'code' => $code,
        ]);
    }

    public function findAllByName(string $name): array
    {
        return $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.name LIKE "%:name%"')
            ->setParameter('name', $name)
            ->getQuery()
            ->getResult();
    }
}

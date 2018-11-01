<?php

declare(strict_types=1);

namespace App\Countries\Repository;

use App\Countries\Entity\ImdbCountry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ImdbCountry|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImdbCountry|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImdbCountry[]    findAll()
 * @method ImdbCountry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImdbCountryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ImdbCountry::class);
    }
}

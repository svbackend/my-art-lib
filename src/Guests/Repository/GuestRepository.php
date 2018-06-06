<?php

declare(strict_types=1);

namespace App\Guests\Repository;

use App\Guests\Entity\GuestSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method GuestSession|null find($id, $lockMode = null, $lockVersion = null)
 * @method GuestSession|null findOneBy(array $criteria, array $orderBy = null)
 * @method GuestSession[]    findAll()
 * @method GuestSession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GuestRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GuestSession::class);
    }
}

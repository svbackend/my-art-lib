<?php
declare(strict_types=1);

namespace App\Users\Repository;

use App\Users\Entity\ConfirmationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ConfirmationToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfirmationToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfirmationToken[]    findAll()
 * @method ConfirmationToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfirmationTokenRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ConfirmationToken::class);
    }

    public function findByToken(string $token): ?ConfirmationToken
    {
        return $this->findOneBy([
            'token' => $token
        ]);
    }
}

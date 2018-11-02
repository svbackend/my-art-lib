<?php

declare(strict_types=1);

namespace App\Users\Repository;

use App\Users\Entity\ConfirmationToken;
use App\Users\Entity\User;
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

    /**
     * @param string $token
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return ConfirmationToken|null
     */
    public function findByToken(string $token): ?ConfirmationToken
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->where('t.token = :token AND t.expires_at >= :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param User   $user
     * @param string $type
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return ConfirmationToken|null
     */
    public function findByUserAndType(User $user, string $type): ?ConfirmationToken
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->where('t.user = :user AND t.type = :type AND t.expires_at >= :now')
            ->setParameter('user', $user->getId())
            ->setParameter('type', $type)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }
}

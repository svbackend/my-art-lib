<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\ConfirmationToken;
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

    public function findOneOrNullByToken(string $token): ?ConfirmationToken
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT t, u FROM App\Entity\ConfirmationToken t JOIN t.user u WHERE t.token = :token'
            )->setParameter('token', $token);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (\Doctrine\ORM\NonUniqueResultException $e) {
            return null;
        }
    }
}

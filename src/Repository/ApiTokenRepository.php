<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApiToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ApiToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiToken[]    findAll()
 * @method ApiToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    //todo user doesn't loaded
    public function findByToken(string $token): ?ApiToken
    {
        return $this->findOneByToken($token);
        /*return $this->findOneBy([
            'token' => $token,
        ]);*/
    }

    public function findOneByToken(string $token): ?ApiToken
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT t, u FROM App\Entity\ApiToken t JOIN t.user u WHERE t.token = :token'
            )->setParameter('token', $token);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (\Doctrine\ORM\NonUniqueResultException $e) {
            return null;
        }
    }

    public function findOneByIdJoinedToCategory($productId)
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT p, c FROM AppBundle:Product p
        JOIN p.category c
        WHERE p.id = :id'
            )->setParameter('id', $productId);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}

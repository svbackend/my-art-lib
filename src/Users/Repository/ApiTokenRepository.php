<?php
declare(strict_types=1);

namespace App\Users\Repository;

use App\Users\Entity\ApiToken;
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

    public function findByToken(string $token): ?ApiToken
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT t, u FROM App\Users\Entity\ApiToken t JOIN t.user u WHERE t.token = :token'
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

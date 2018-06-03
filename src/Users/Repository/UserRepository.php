<?php
declare(strict_types=1);

namespace App\Users\Repository;

use App\Users\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param string $username
     * @return User|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByUsername($username): ?User
    {
        $username = mb_strtolower($username);
        return $this->createQueryBuilder('u')
            ->where('LOWER(u.username) = :username OR LOWER(u.email) = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array $criteria
     * @return mixed
     */
    public function getUsersByCriteria(array $criteria)
    {
        $field = key($criteria);
        $value = mb_strtolower(reset($criteria));

        return $this->createQueryBuilder('u')
            ->where("LOWER(u.{$field}) = :value")
            ->setParameter('value', $value)
            ->getQuery()
            ->getResult();
    }

    /**
     * I do not understand why getSingleScalarResult throws NoResultException but it does
     *
     * @param array $criteria
     * @return bool
     */
    public function isUserExists(array $criteria): bool
    {
        $field = key($criteria);
        $value = mb_strtolower($criteria[$field]);

        try {
            $user = $this->createQueryBuilder('u')
                ->where("LOWER(u.{$field}) = :value")
                ->setParameter('value', $value)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NonUniqueResultException $nonUniqueResultException) {
            return true;
        } catch (NoResultException $noResultException) {
            return false;
        }

        return $user !== null;
    }
}

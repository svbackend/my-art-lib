<?php
declare(strict_types=1);

namespace App\Movies\Repository;

use App\Guests\Entity\GuestSession;
use App\Movies\Entity\Movie;
use App\Users\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    private function getBaseQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.translations', 'mt')
            ->addSelect('mt')
            ->leftJoin('m.genres', 'mg')
            ->addSelect('mg')
            ->leftJoin('mg.translations', 'mgt')
            ->addSelect('mgt');
    }

    public function findAllWithIsUserWatchedFlag(User $user)
    {
        $result = $this->getBaseQuery()
            ->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'uwm.user = :user_id') // if this relation exists then user has already watched this movie
            ->addSelect('uwm')
            ->setParameter('user_id', $user->getId())
            ->orderBy('m.id', 'DESC')
            ->getQuery();

        return $result;
    }

    public function findAllWithIsGuestWatchedFlag(?GuestSession $guestSession)
    {
        $guestSessionId = $guestSession ? $guestSession->getId() : 0;

        $result = $this->getBaseQuery()
            ->leftJoin('m.guestWatchedMovie', 'gwm', 'WITH', 'gwm.guestSession = :guest_session_id') // if this relation exists then guest has already watched this movie
            ->addSelect('gwm')
            ->setParameter('guest_session_id', $guestSessionId)
            ->orderBy('m.id', 'DESC')
            ->getQuery();

        return $result;
    }

    /**
     * @param array $ids
     * @return array|Movie[]
     */
    public function findAllByIds(array $ids)
    {
        $result = $this->getBaseQuery()
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findAllQuery()
    {
        $result = $this->getBaseQuery()
            ->orderBy('m.id', 'DESC')
            ->getQuery();

        return $result;
    }

    public function findByTitleQuery(string $query)
    {
        $query = mb_strtolower($query);
        $result = $this->getBaseQuery()
            ->andWhere('LOWER(m.originalTitle) LIKE :title OR LOWER(mt.title) LIKE :title')
            ->setParameter('title', "%{$query}%")
            ->getQuery();

        return $result;
    }

    public function findOneByIdOrTmdbId(?int $id = null, ?int $tmdb_id = null)
    {
        if ($id === null && $tmdb_id === null) {
            throw new \InvalidArgumentException('Movie ID or TMDB ID should be provided');
        }

        return $id ? $this->find($id) : $this->findOneBy(['tmdb.id' => $tmdb_id]);
    }
}

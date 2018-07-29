<?php

declare(strict_types=1);

namespace App\Movies\Repository;

use App\Guests\Entity\GuestSession;
use App\Movies\Entity\Movie;
use App\Users\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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

    public function findAllByIdsWithFlags(array $ids, int $userId)
    {

        $result = $this->getBaseQuery()
            ->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'uwm.user = :user_id') // if this relation exists then user has already watched this movie
            ->addSelect('uwm')
            ->leftJoin('m.userRecommendedMovie', 'urm', 'WITH', 'urm.user = :user_id')
            ->addSelect('urm')
            ->where('m.id IN (:ids)')
            ->setParameter('user_id', $userId)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        // Sorting here because ORDER BY FIELD(m.id, ...$ids) not working in postgres, we need to use joins on sorted table and so on, but I dont want to
        // todo => add sorting to sql
        $reversedIds = array_flip($ids);
        usort($result, function (Movie $movie1, Movie $movie2) use ($reversedIds) {
            return $reversedIds[$movie1->getId()] <=> $reversedIds[$movie2->getId()];
        });

        return $result;
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
     *
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

    /**
     * @param array $ids
     *
     * @return array|Movie[]
     */
    public function findAllByTmdbIds(array $ids)
    {
        $result = $this->getBaseQuery()
            ->where('m.tmdb.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function getAllWatchedMoviesByUserId(int $userId): Query
    {
        $result = $this->getBaseQuery()
            ->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'uwm.user = :user_id')
            ->addSelect('uwm')
            ->setParameter('user_id', $userId)
            ->andWhere('uwm.id != 0')
            ->orderBy('uwm.id', 'DESC')
            ->getQuery();

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

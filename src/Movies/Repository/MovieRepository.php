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
            ->leftJoin('m.translations', 'mt', null, null, 'mt.locale')
            ->addSelect('mt')
            ->leftJoin('m.genres', 'mg')
            ->addSelect('mg')
            ->leftJoin('mg.translations', 'mgt', null, null, 'mgt.locale')
            ->addSelect('mgt');
    }

    /**
     * @param int       $id
     * @param User|null $user
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Movie|null
     */
    public function findOneForMoviePage(int $id, ?User $user = null): ?Movie
    {
        if ($user === null) {
            return $this->getBaseQuery()
                ->where('m.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getSingleResult();
        }

        $result = $this->getBaseQuery()
            ->where('m.id = :id')
            ->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'uwm.user = :user_id')
            ->addSelect('uwm')
            ->leftJoin('m.userRecommendedMovie', 'urm', 'WITH', 'urm.user = :user_id AND urm.originalMovie = :id')
            ->addSelect('urm')
            ->leftJoin('m.userInterestedMovie', 'uim', 'WITH', 'uim.user = :user_id AND uim.movie = :id')
            ->addSelect('uim')
            ->setParameter('user_id', $user->getId())
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleResult();

        return $result;
    }

    public function findAllByIdsWithFlags(array $ids, int $userId, int $originalMovieId)
    {
        $result = $this->getBaseQuery()
            ->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'uwm.user = :user_id') // if this relation exists then user has already watched this movie
            ->addSelect('uwm')
            ->leftJoin('m.userRecommendedMovie', 'urm', 'WITH', 'urm.user = :user_id AND urm.originalMovie = :original_movie_id')
            ->addSelect('urm')
            ->where('m.id IN (:ids)')
            ->setParameter('user_id', $userId)
            ->setParameter('original_movie_id', $originalMovieId)
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

    public function findAllByIdsWithoutFlags(array $ids)
    {
        $result = $this->findAllByIds($ids);

        // Sorting here because ORDER BY FIELD(m.id, ...$ids) not working in postgres, we need to use joins on sorted table and so on, but I dont want to
        // todo => add sorting to sql
        $reversedIds = array_flip($ids);
        usort($result, function (Movie $movie1, Movie $movie2) use ($reversedIds) {
            return $reversedIds[$movie1->getId()] <=> $reversedIds[$movie2->getId()];
        });

        return $result;
    }

    public function findAllWithIsWatchedFlag(?User $user = null, ?GuestSession $guest = null): array
    {
        $items = $this->getBaseQuery();

        if ($user !== null) {
            $items
                ->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'uwm.user = :user_id')
                ->addSelect('uwm')
                ->setParameter('user_id', $user->getId());
        }

        if ($guest !== null) {
            $items
                ->leftJoin('m.guestWatchedMovie', 'gwm', 'WITH', 'gwm.guestSession = :guest_id')
                ->addSelect('gwm')
                ->setParameter('guest_id', $guest->getId());
        }

        $items->where('m.id IN (:ids)');

        /** Ids query */

        $ids = $this->createQueryBuilder('m')
            ->select('m.id')
            ->orderBy('m.id', 'DESC');

        /** Count query */

        $count = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)');

        return [$items->getQuery(), $ids->getQuery(), $count->getQuery()];
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
    public function findAllByIdsWithSimilarMovies(array $ids): array
    {
        $result = $this->createQueryBuilder('m')
            ->leftJoin('m.similarMovies', 'sm')
            ->addSelect('sm')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getScalarResult();

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

    /**
     * @param array $ids
     *
     * @return array of int
     */
    public function findAllIdsByTmdbIds(array $ids)
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.id, m.tmdb.voteAverage, m.releaseDate')
            ->where('m.tmdb.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getScalarResult();

        return $result;
    }

    public function getAllWatchedMoviesByUserId(int $ownerId, ?User $currentUser = null): Query
    {
        $result = $this->getBaseQuery()
            ->leftJoin('m.ownerWatchedMovie', 'owm', 'WITH', 'owm.user = :owner_id')
            ->addSelect('owm')
            ->setParameter('owner_id', $ownerId)
            ->andWhere('owm.id != 0')
            ->orderBy('owm.id', 'DESC');

        if ($currentUser !== null) {
            $result->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'uwm.user = :user_id')
                ->addSelect('uwm')
                ->setParameter('user_id', $currentUser->getId());
        }

        return $result->getQuery();
    }

    public function getAllInterestedMoviesByUserId(int $profileOwnerId, ?User $currentUser = null): Query
    {
        $result = $this->getBaseQuery()
            ->leftJoin('m.userInterestedMovie', 'uim', 'WITH', 'uim.user = :owner_id')
            ->addSelect('uim')
            ->setParameter('owner_id', $profileOwnerId)
            ->andWhere('uim.id != 0')
            ->orderBy('uim.id', 'DESC');

        if ($currentUser !== null) {
            $result->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'uwm.user = :current_user_id')
                ->addSelect('uwm')
                ->setParameter('current_user_id', $currentUser->getId());
        }

        return $result->getQuery();
    }

    public function findAllByActor(int $actorId, ?User $currentUser = null): Query
    {
        $result = $this->getBaseQuery()
            ->leftJoin('m.actors', 'ma', 'WITH', 'ma.actor = :actor AND ma.movie = m')
            ->setParameter('actor', $actorId)
            ->andWhere('ma.id != 0')
            ->orderBy('m.releaseDate', 'DESC');

        if ($currentUser !== null) {
            $result->leftJoin('m.userWatchedMovie', 'uwm', 'WITH', 'uwm.user = :user_id')
                ->addSelect('uwm')
                ->setParameter('user_id', $currentUser->getId());
        }

        return $result->getQuery();
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

    public function findByTitleWithUserRecommendedMovieQuery(string $query, int $userId, int $originalMovieId)
    {
        $query = mb_strtolower($query);
        $result = $this->getBaseQuery()
            ->andWhere('LOWER(m.originalTitle) LIKE :title OR LOWER(mt.title) LIKE :title')
            ->setParameter('title', "%{$query}%")
            ->leftJoin('m.userRecommendedMovie', 'urm', 'WITH', 'urm.user = :user_id AND urm.originalMovie = :movie_id')
            ->addSelect('urm')
            ->setParameter('user_id', $userId)
            ->setParameter('movie_id', $originalMovieId)
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

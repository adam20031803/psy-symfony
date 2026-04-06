<?php

namespace App\Repository;

use App\Entity\DailyCheckin;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DailyCheckin>
 */
class DailyCheckinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyCheckin::class);
    }

    public function findOneByUserAndDate(User $user, \DateTimeInterface $date): ?DailyCheckin
    {
        $d = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);

        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.checkinDate = :d')
            ->setParameter('user', $user)
            ->setParameter('d', $d)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return DailyCheckin[]
     */
    public function findByUserOrdered(User $user, int $limit = 500): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.checkinDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return DailyCheckin[]
     */
    public function findLastDaysForUser(User $user, int $days): array
    {
        $since = (new \DateTimeImmutable('today'))
            ->modify(sprintf('-%d days', max(0, $days - 1)))
            ->setTime(0, 0, 0);

        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.checkinDate >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->orderBy('c.checkinDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function averageMoodLastDays(User $user, int $days): ?float
    {
        $since = (new \DateTimeImmutable('today'))
            ->modify(sprintf('-%d days', max(0, $days - 1)))
            ->setTime(0, 0, 0);

        $result = $this->createQueryBuilder('c')
            ->select('AVG(c.moodRating)')
            ->andWhere('c.user = :user')
            ->andWhere('c.checkinDate >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : null;
    }

    public function averageProductivityLastDays(User $user, int $days): ?float
    {
        $since = (new \DateTimeImmutable('today'))
            ->modify(sprintf('-%d days', max(0, $days - 1)))
            ->setTime(0, 0, 0);

        $result = $this->createQueryBuilder('c')
            ->select('AVG(c.productivityLevel)')
            ->andWhere('c.user = :user')
            ->andWhere('c.checkinDate >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : null;
    }

    public function hasCheckinToday(User $user): bool
    {
        $today = new \DateTimeImmutable('today');

        $count = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.user = :user')
            ->andWhere('c.checkinDate = :today')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Search + period filter for reflections (debounced on client; server filters list).
     *
     * @return DailyCheckin[]
     */
    public function findFilteredForUser(User $user, ?string $search, string $period): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.checkinDate', 'DESC');

        $today = (new \DateTimeImmutable('today'))->setTime(0, 0, 0);

        match ($period) {
            'today' => $qb->andWhere('c.checkinDate = :day')->setParameter('day', $today),
            'week' => $qb->andWhere('c.checkinDate >= :w')->setParameter('w', $today->modify('-6 days')),
            'month' => $qb->andWhere('c.checkinDate >= :m')->setParameter('m', (new \DateTimeImmutable('today'))->modify('-29 days')->setTime(0, 0, 0)),
            default => null,
        };

        if ($search !== null && $search !== '') {
            $term = '%'.mb_strtolower($search).'%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(c.whatWentWell) LIKE :t',
                'LOWER(c.whatCouldImprove) LIKE :t',
                'LOWER(c.gratitude) LIKE :t',
                'LOWER(c.mainChallenges) LIKE :t',
                'LOWER(c.biggestWins) LIKE :t',
                'LOWER(c.additionalNotes) LIKE :t'
            ))->setParameter('t', $term);
        }

        return $qb->getQuery()->getResult();
    }
}

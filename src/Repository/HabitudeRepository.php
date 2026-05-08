<?php

namespace App\Repository;

use App\Entity\Habitude;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Habitude>
 */
class HabitudeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Habitude::class);
    }

    /**
     * Find habits by search text, category and user
     */
    public function searchByCategoryAndText(?string $category, ?string $searchText, ?int $userId = null): array
    {
        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.user', 'u')
            ->addSelect('u');

        if ($userId !== null) {
            $qb->andWhere('h.user = :userId')
               ->setParameter('userId', $userId);
        }

        if ($category && $category !== 'Toutes') {
            $qb->andWhere('h.category = :category')
               ->setParameter('category', $category);
        }

        if ($searchText) {
            $qb->andWhere('h.title LIKE :search OR h.description LIKE :search')
               ->setParameter('search', '%' . $searchText . '%');
        }

        $qb->orderBy('h.startDate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get habits completed today for a user
     */
    public function findCompletedToday(?int $userId = null): array
    {
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');

        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.completions', 'c')
            ->andWhere('c.completedAt >= :today')
            ->andWhere('c.completedAt < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow);

        if ($userId !== null) {
            $qb->andWhere('h.user = :userId')
               ->setParameter('userId', $userId);
        }

        return $qb->getQuery()->getResult();
    }

    public function countCompletedToday(?int $userId = null): int
    {
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');

        $qb = $this->createQueryBuilder('h')
            ->select('COUNT(DISTINCT h.id)')
            ->leftJoin('h.completions', 'c')
            ->andWhere('c.completedAt >= :today')
            ->andWhere('c.completedAt < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow);

        if ($userId !== null) {
            $qb->andWhere('h.user = :userId')
               ->setParameter('userId', $userId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get global statistics
     */
    public function getGlobalStats(?int $userId = null): array
    {
        $qbTotal = $this->createQueryBuilder('h')
            ->select('COUNT(h.id)');
        if ($userId !== null) {
            $qbTotal->andWhere('h.user = :userId')
                    ->setParameter('userId', $userId);
        }
        $totalHabits = $qbTotal->getQuery()->getSingleScalarResult();

        $qbActive = $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.active = :active')
            ->setParameter('active', true);
        if ($userId !== null) {
            $qbActive->andWhere('h.user = :userId')
                     ->setParameter('userId', $userId);
        }
        $activeHabits = $qbActive->getQuery()->getSingleScalarResult();

        $qbStreak = $this->createQueryBuilder('h')
            ->select('SUM(h.currentStreak)')
            ->andWhere('h.active = :active')
            ->setParameter('active', true);
        if ($userId !== null) {
            $qbStreak->andWhere('h.user = :userId')
                     ->setParameter('userId', $userId);
        }
        $globalStreak = $qbStreak->getQuery()->getSingleScalarResult() ?? 0;

        return [
            'total' => (int) $totalHabits,
            'active' => (int) $activeHabits,
            'globalStreak' => (int) $globalStreak,
        ];
    }
}

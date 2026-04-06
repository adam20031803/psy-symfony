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
     * Find habits by search text and category
     */
    public function searchByCategoryAndText(?string $category, ?string $searchText): array
    {
        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.user', 'u')
            ->addSelect('u');

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
    public function findCompletedToday(?int $userId): array
    {
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');

        return $this->createQueryBuilder('h')
            ->leftJoin('h.completions', 'c')
            ->andWhere('h.user = :userId')
            ->andWhere('c.completedAt >= :today')
            ->andWhere('c.completedAt < :tomorrow')
            ->setParameter('userId', $userId)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get global statistics
     */
    public function getGlobalStats(?int $userId): array
    {
        $totalHabits = $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        $activeHabits = $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.user = :userId')
            ->andWhere('h.active = :active')
            ->setParameter('userId', $userId)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $globalStreak = $this->createQueryBuilder('h')
            ->select('SUM(h.currentStreak)')
            ->andWhere('h.user = :userId')
            ->andWhere('h.active = :active')
            ->setParameter('userId', $userId)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return [
            'total' => (int) $totalHabits,
            'active' => (int) $activeHabits,
            'globalStreak' => (int) $globalStreak,
        ];
    }
}

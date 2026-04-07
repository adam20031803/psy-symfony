<?php

namespace App\Repository;

use App\Entity\Exercise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercise>
 */
class ExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercise::class);
    }

    /**
     * Search exercises by name or category keyword.
     */
    public function searchByQuery(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.name LIKE :q OR e.category LIKE :q OR e.description LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filter exercises by category.
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.category = :cat')
            ->setParameter('cat', $category)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get last N exercises ordered by creation date.
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count exercises grouped by category.
     */
    public function countByCategory(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.category, COUNT(e.id) as total')
            ->groupBy('e.category')
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\Program;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Program>
 */
class ProgramRepository extends ServiceEntityRepository
{
    private const DEFAULT_RESULT_LIMIT = 50;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Program::class);
    }

    /**
     * Return only published programs for Front Office.
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isPublished = true')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(self::DEFAULT_RESULT_LIMIT)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search programs by title or goal keyword.
     */
    public function searchByQuery(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.title LIKE :q OR p.goal LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(self::DEFAULT_RESULT_LIMIT)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get last N programs ordered by creation date.
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count published programs.
     */
    public function countPublished(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isPublished = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count draft (unpublished) programs.
     */
    public function countDraft(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isPublished = false')
            ->getQuery()
            ->getSingleScalarResult();
    }
}

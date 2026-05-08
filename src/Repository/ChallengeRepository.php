<?php

namespace App\Repository;

use App\Entity\Challenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Challenge>
 */
class ChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Challenge::class);
    }

    /**
     * Returns counts indexed by status (statut => total).
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.statut AS statut', 'COUNT(c.id) AS cnt')
            ->groupBy('c.statut')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $status = (string) ($row['statut'] ?? '');
            $result[$status] = (int) ($row['cnt'] ?? 0);
        }

        return $result;
    }
}

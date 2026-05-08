<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

    /**
     * Returns counts indexed by status (statut => total).
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.statut AS statut', 'COUNT(r.id) AS cnt')
            ->groupBy('r.statut')
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

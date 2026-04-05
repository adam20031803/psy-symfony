<?php

namespace App\Repository;

use App\Entity\MentalEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MentalEntry>
 */
class MentalEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MentalEntry::class);
    }

    /** @return MentalEntry[] */
    public function findForDashboard(): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.mood', 'm')->addSelect('m')
            ->orderBy('e.entryDate', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\MentalTip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MentalTip>
 */
class MentalTipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MentalTip::class);
    }

    /** @return MentalTip[] */
    public function findForDashboard(): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.mood', 'm')->addSelect('m')
            ->orderBy('m.moodName', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

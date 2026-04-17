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

    /** @return MentalTip[] */
    public function findForListing(?string $search, string $sort, string $dir): array
    {
        $allowedSorts = [
            'mood' => 'm.moodName',
            'tip' => 't.tipText',
            'id' => 't.id',
        ];
        $sortField = $allowedSorts[$sort] ?? $allowedSorts['mood'];
        $direction = 'DESC' === strtoupper($dir) ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('t')
            ->join('t.mood', 'm')->addSelect('m');

        $term = mb_strtolower(trim((string) $search));
        if ('' !== $term) {
            $qb->andWhere('LOWER(t.tipText) LIKE :term OR LOWER(m.moodName) LIKE :term')
                ->setParameter('term', '%'.$term.'%');
        }

        return $qb
            ->orderBy($sortField, $direction)
            ->addOrderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

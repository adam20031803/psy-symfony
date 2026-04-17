<?php

namespace App\Repository;

use App\Entity\Mood;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mood>
 */
class MoodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mood::class);
    }

    /** @return Mood[] */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.moodName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Mood[] */
    public function findForListing(?string $search, string $sort, string $dir): array
    {
        $allowedSorts = [
            'name' => 'm.moodName',
            'createdAt' => 'm.createdAt',
            'id' => 'm.id',
        ];

        $sortField = $allowedSorts[$sort] ?? $allowedSorts['name'];
        $direction = 'DESC' === strtoupper($dir) ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('m');
        $term = mb_strtolower(trim((string) $search));
        if ('' !== $term) {
            $qb->andWhere('LOWER(m.moodName) LIKE :term')
                ->setParameter('term', '%'.$term.'%');
        }

        return $qb
            ->orderBy($sortField, $direction)
            ->addOrderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

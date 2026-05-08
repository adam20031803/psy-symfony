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
            ->leftJoin('e.mood', 'm')->addSelect('m')
            ->orderBy('e.entryDate', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Même ordre que findForDashboard() : entry_date DESC, id DESC (uniquement entrées avec mood valide).
     */
    public function findOneByOrderedListOffset(int $zeroBasedOffset): ?MentalEntry
    {
        $offset = max(0, $zeroBasedOffset);

        return $this->createQueryBuilder('e')
            ->leftJoin('e.mood', 'm')->addSelect('m')
            ->orderBy('e.entryDate', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return MentalEntry[] */
    public function findForListing(?string $search, string $sort, string $dir): array
    {
        $allowedSorts = [
            'mood' => 'm.moodName',
            'date' => 'e.entryDate',
            'level' => 'e.emotionLevel',
            'activity' => 'e.activity',
            'id' => 'e.id',
        ];
        $sortField = $allowedSorts[$sort] ?? $allowedSorts['date'];
        $direction = 'DESC' === strtoupper($dir) ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.mood', 'm')->addSelect('m');

        $term = mb_strtolower(trim((string) $search));
        if ('' !== $term) {
            $qb->andWhere('LOWER(COALESCE(m.moodName, \'\')) LIKE :term OR LOWER(e.activity) LIKE :term OR LOWER(COALESCE(e.note, \'\')) LIKE :term')
                ->setParameter('term', '%'.$term.'%');
        }

        return $qb
            ->orderBy($sortField, $direction)
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

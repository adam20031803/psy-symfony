<?php

namespace App\Repository;

use App\Entity\Mood;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
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

    /** Même ordre que findAllOrderedByName() : mood_name ASC puis id. */
    public function findOneByOrderedListOffset(int $zeroBasedOffset): ?Mood
    {
        $offset = max(0, $zeroBasedOffset);
        $em = $this->getEntityManager();
        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(Mood::class, 'm');
        $select = $rsm->generateSelectClause(['m' => 'm']);
        $sql = sprintf(
            'SELECT %s FROM mood m ORDER BY m.mood_name ASC, m.id ASC LIMIT 1 OFFSET %d',
            $select,
            $offset
        );

        $query = $em->createNativeQuery($sql, $rsm);
        $rows = $query->getResult();

        return ($rows[0] ?? null) instanceof Mood ? $rows[0] : null;
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

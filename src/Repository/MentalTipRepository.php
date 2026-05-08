<?php

namespace App\Repository;

use App\Entity\MentalTip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
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

    /**
     * Liste affichage dashboard : une ligne par enregistrement SQL.
     * Si la table a plusieurs lignes avec le même id (ex. id=0 sans PK/auto_increment),
     * findForDashboard() ne renvoie qu’une entité Doctrine — ici toutes les lignes apparaissent.
     *
     * @return list<array{id: int|string|null, tip_text: string, mood_name: string}>
     */
    public function findForDashboardList(): array
    {
        $sql = <<<'SQL'
            SELECT t.id AS id, t.tip_text AS tip_text, COALESCE(m.mood_name, '—') AS mood_name
            FROM mental_tip t
            LEFT JOIN mood m ON m.id = t.mood_id
            ORDER BY COALESCE(m.mood_name, '') ASC, t.id ASC
            SQL;

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);

        return array_map(static function (array $row): array {
            return [
                'id' => $row['id'] ?? null,
                'tip_text' => (string) ($row['tip_text'] ?? ''),
                'mood_name' => (string) ($row['mood_name'] ?? '—'),
            ];
        }, $rows);
    }

    /**
     * Charge la nième ligne (0-based) dans le même ordre que findForDashboardList().
     * Utile quand plusieurs lignes partagent le même id en base : find() ne suffit pas.
     */
    public function findOneByOrderedListOffset(int $zeroBasedOffset): ?MentalTip
    {
        $offset = max(0, $zeroBasedOffset);
        $em = $this->getEntityManager();
        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(MentalTip::class, 't');
        $select = $rsm->generateSelectClause(['t' => 't']);
        $sql = sprintf(
            'SELECT %s FROM mental_tip t LEFT JOIN mood m ON m.id = t.mood_id ORDER BY COALESCE(m.mood_name, \'\') ASC, t.id ASC, t.tip_text ASC LIMIT 1 OFFSET %d',
            $select,
            $offset
        );

        $query = $em->createNativeQuery($sql, $rsm);
        $rows = $query->getResult();

        return ($rows[0] ?? null) instanceof MentalTip ? $rows[0] : null;
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

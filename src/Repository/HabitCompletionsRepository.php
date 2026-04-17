<?php
namespace App\Repository;
use App\Entity\HabitCompletions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class HabitCompletionsRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, HabitCompletions::class); }

    public function countTodayCompletions($user): int
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.habitude)')
            ->where('c.user = :user')
            ->andWhere('c.completedAt >= :today')
            ->andWhere('c.completedAt < :tomorrow')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findIdsCompletedToday($user): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        $res = $this->createQueryBuilder('c')
            ->select('DISTINCT hab.id')
            ->join('c.habitude', 'hab')
            ->where('c.user = :user')
            ->andWhere('c.completedAt >= :today')
            ->andWhere('c.completedAt < :tomorrow')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getScalarResult();

        return array_column($res, 'id');
    }
}

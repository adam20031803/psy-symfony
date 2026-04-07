<?php

namespace App\Repository;

use App\Entity\ProgramAssignment;
use App\Entity\Program;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramAssignment>
 */
class ProgramAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProgramAssignment::class);
    }

    /** All assignments for a given user, newest first */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Pending assignments for a given user */
    public function findPendingByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', ProgramAssignment::STATUS_PENDING)
            ->getQuery()
            ->getResult();
    }

    /** Count accepted assignments for a program */
    public function countAccepted(Program $program): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.program = :program')
            ->andWhere('a.status = :status')
            ->setParameter('program', $program)
            ->setParameter('status', ProgramAssignment::STATUS_ACCEPTED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Count declined assignments for a program */
    public function countDeclined(Program $program): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.program = :program')
            ->andWhere('a.status = :status')
            ->setParameter('program', $program)
            ->setParameter('status', ProgramAssignment::STATUS_DECLINED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Count total assignments for a program */
    public function countTotal(Program $program): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.program = :program')
            ->setParameter('program', $program)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Get all assignments for a program with user data (for admin view) */
    public function findByProgramWithUsers(Program $program): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('a.program = :program')
            ->setParameter('program', $program)
            ->orderBy('a.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Build stats map: program_id => [total, accepted, declined, pending] */
    public function buildStatsMap(array $programs): array
    {
        $stats = [];
        foreach ($programs as $program) {
            $stats[$program->getId()] = [
                'total'    => 0,
                'accepted' => 0,
                'declined' => 0,
                'pending'  => 0,
            ];
        }

        if (empty($programs)) {
            return $stats;
        }

        $rows = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.program) as program_id, a.status, COUNT(a.id) as cnt')
            ->where('a.program IN (:programs)')
            ->setParameter('programs', $programs)
            ->groupBy('a.program, a.status')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $pid = $row['program_id'];
            $stats[$pid]['total'] += (int) $row['cnt'];
            $stats[$pid][$row['status']] = (int) $row['cnt'];
        }

        return $stats;
    }
}

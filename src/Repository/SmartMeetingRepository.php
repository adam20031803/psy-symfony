<?php

namespace App\Repository;

use App\Entity\SmartMeeting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SmartMeeting>
 *
 * @method SmartMeeting|null find($id, $lockMode = null, $lockVersion = null)
 * @method SmartMeeting|null findOneBy(array $criteria, array $orderBy = null)
 * @method SmartMeeting[]    findAll()
 * @method SmartMeeting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmartMeetingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SmartMeeting::class);
    }

    public function findPendingMeetingsByChallenge($challenge)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.challenge = :val')
            ->andWhere('s.status = :status')
            ->setParameter('val', $challenge)
            ->setParameter('status', 'SCHEDULED')
            ->getQuery()
            ->getResult();
    }
}

<?php
namespace App\Repository;
use App\Entity\DailyCheckin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class DailyCheckinRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, DailyCheckin::class); }
}

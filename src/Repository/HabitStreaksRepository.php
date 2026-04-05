<?php
namespace App\Repository;
use App\Entity\HabitStreaks;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class HabitStreaksRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, HabitStreaks::class); }
}

<?php
namespace App\Repository;
use App\Entity\HabitCompletions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class HabitCompletionsRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, HabitCompletions::class); }
}

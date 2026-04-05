<?php
namespace App\Repository;
use App\Entity\ChallengeRecompense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ChallengeRecompenseRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ChallengeRecompense::class); }
}

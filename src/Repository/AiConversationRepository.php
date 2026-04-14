<?php

namespace App\Repository;

use App\Entity\AiConversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AiConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiConversation::class);
    }

    public function findOrCreateForUser(User $user): AiConversation
    {
        $conv = $this->findOneBy(['user' => $user], ['createdAt' => 'DESC']);
        return $conv ?? new AiConversation();
    }

    public function findActiveForUser(User $user): ?AiConversation
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.lastActivityAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
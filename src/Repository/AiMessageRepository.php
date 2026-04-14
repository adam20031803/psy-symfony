<?php

namespace App\Repository;

use App\Entity\AiConversation;
use App\Entity\AiMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AiMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiMessage::class);
    }

    public function findByConversation(AiConversation $conv, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conv')
            ->setParameter('conv', $conv)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnread(AiConversation $conv): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.conversation = :conv AND m.isRead = false AND m.role = :role')
            ->setParameter('conv', $conv)
            ->setParameter('role', AiMessage::ROLE_ASSISTANT)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
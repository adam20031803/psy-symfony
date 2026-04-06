<?php

// src/Repository/PostLikeRepository.php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostLike>
 */
class PostLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostLike::class);
    }

    /**
     * Retourne le vote (like/dislike) d'un utilisateur pour un post donné, ou null.
     */
    public function findUserLike(Post $post, User $user): ?PostLike
    {
        return $this->findOneBy(['post' => $post, 'user' => $user]);
    }

    /**
     * Compte les likes d'un post.
     */
    public function countLikes(Post $post): int
    {
        return (int) $this->createQueryBuilder('pl')
            ->select('COUNT(pl.id)')
            ->andWhere('pl.post = :post')
            ->andWhere('pl.type = :type')
            ->setParameter('post', $post)
            ->setParameter('type', PostLike::TYPE_LIKE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les dislikes d'un post.
     */
    public function countDislikes(Post $post): int
    {
        return (int) $this->createQueryBuilder('pl')
            ->select('COUNT(pl.id)')
            ->andWhere('pl.post = :post')
            ->andWhere('pl.type = :type')
            ->setParameter('post', $post)
            ->setParameter('type', PostLike::TYPE_DISLIKE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTotalLikesOnUserPosts(User $user): int
    {
        return (int) $this->createQueryBuilder('pl')
            ->select('COUNT(pl.id)')
            ->join('pl.post', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('pl.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', PostLike::TYPE_LIKE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

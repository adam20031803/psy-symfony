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

    public function countAllLikes(): int
    {
        return (int) $this->createQueryBuilder('pl')
            ->select('COUNT(pl.id)')
            ->andWhere('pl.type = :type')
            ->setParameter('type', PostLike::TYPE_LIKE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int[] $postIds
     * @return array<int, int> [postId => likesCount]
     */
    public function countLikesForPosts(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('pl')
            ->select('IDENTITY(pl.post) AS post_id', 'COUNT(pl.id) AS cnt')
            ->andWhere('pl.type = :type')
            ->andWhere('pl.post IN (:postIds)')
            ->setParameter('type', PostLike::TYPE_LIKE)
            ->setParameter('postIds', $postIds)
            ->groupBy('pl.post')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['post_id']] = (int) $row['cnt'];
        }

        return $result;
    }
}

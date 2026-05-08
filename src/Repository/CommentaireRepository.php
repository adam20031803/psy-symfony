<?php
namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    /**
     * @param int[] $postIds
     * @return array<int, int> [postId => commentsCount]
     */
    public function countForPosts(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.post) AS post_id', 'COUNT(c.id) AS cnt')
            ->andWhere('c.post IN (:postIds)')
            ->setParameter('postIds', $postIds)
            ->groupBy('c.post')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['post_id']] = (int) $row['cnt'];
        }

        return $result;
    }
}

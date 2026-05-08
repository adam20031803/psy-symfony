<?php

// src/Repository/PostRepository.php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    private const DEFAULT_RESULT_LIMIT = 50;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Retourne les posts filtrés par recherche textuelle (titre/contenu) et/ou catégorie.
     */
    public function findByFilters(?string $search, ?int $categorieId): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(self::DEFAULT_RESULT_LIMIT);

        if ($search) {
            $qb->andWhere('p.titre LIKE :q OR p.contenu LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }

        if ($categorieId) {
            $qb->andWhere('c.id = :cat')
               ->setParameter('cat', $categorieId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Optimized lightweight query for dashboard cards.
     *
     * @return Post[]
     */
    public function findLatestForDashboard(int $limit = 3): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

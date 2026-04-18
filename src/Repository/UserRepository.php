<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findOneByPasswordResetToken(string $token): ?User
    {
        return $this->findOneBy(['passwordResetToken' => $token]);
    }

    /**
     * Retrieve users (clients) along with their total tracked workouts and last activity date.
     */
    public function findClientsWithFitnessStats(): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.id', 'u.nom', 'u.prenom', 'u.email', 'u.telephone', 'u.age')
            ->addSelect('COUNT(wp.id) AS total_workouts')
            ->addSelect('MAX(wp.date) AS last_workout_date')
            ->leftJoin('App\Entity\WorkoutProgress', 'wp', 'WITH', 'wp.user = u.id')
            ->where('u.role = :role OR u.role IS NULL')
            ->setParameter('role', 'user')
            ->groupBy('u.id')
            ->orderBy('last_workout_date', 'DESC')
            ->addOrderBy('total_workouts', 'DESC');

        $results = $qb->getQuery()->getArrayResult();
        
        // Ensure last_workout_date is a string or null for easy Excel export
        foreach ($results as &$row) {
            if ($row['last_workout_date'] instanceof \DateTimeInterface) {
                $row['last_workout_date'] = $row['last_workout_date']->format('Y-m-d');
            }
        }
        
        return $results;
    }

    /**
     * Count users grouped by role.
     * Returns e.g. [['role' => 'user', 'cnt' => 10], ...]
     */
    public function countByRole(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.role', 'COUNT(u.id) AS cnt')
            ->groupBy('u.role')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Count new user registrations per month for the last N months.
     * Returns an associative array ['YYYY-MM' => count, ...].
     */
    public function registrationsPerMonth(int $months = 6): array
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $data[(new \DateTime("first day of -$i months"))->format('Y-m')] = 0;
        }

        $rows = $this->createQueryBuilder('u')
            ->select('SUBSTRING(u.createdAt, 1, 7) AS ym', 'COUNT(u.id) AS cnt')
            ->where('u.createdAt >= :since')
            ->setParameter('since', new \DateTime("first day of -" . ($months - 1) . " months"))
            ->groupBy('ym')
            ->orderBy('ym', 'ASC')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            if (isset($data[$row['ym']])) {
                $data[$row['ym']] = (int) $row['cnt'];
            }
        }
        return $data;
    }
}

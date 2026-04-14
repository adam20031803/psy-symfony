<?php

namespace App\Repository;

use App\Entity\AiUserProfile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class AiUserProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiUserProfile::class);
    }

    public function findOrCreateForUser(User $user, EntityManagerInterface $em): AiUserProfile
    {
        $profile = $this->findOneBy(['user' => $user]);
        if (!$profile) {
            $profile = new AiUserProfile();
            $profile->setUser($user);
            $em->persist($profile);
            $em->flush();
        }
        return $profile;
    }
}
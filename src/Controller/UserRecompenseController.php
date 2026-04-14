<?php

namespace App\Controller;

use App\Entity\UserRecompense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserRecompenseController extends AbstractController
{
    #[Route('/my-rewards', name: 'app_my_rewards')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $rewards = $em->getRepository(UserRecompense::class)->findBy(['user' => $user], ['unlockedAt' => 'DESC']);

        return $this->render('recompense/my_rewards.html.twig', [
            'rewards' => $rewards,
        ]);
    }
}

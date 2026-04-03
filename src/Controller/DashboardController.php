<?php

namespace App\Controller;

use App\Repository\ReclamationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(ReclamationRepository $reclamationRepo): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $reclamations = $reclamationRepo->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            5  // afficher les 5 dernières sur le dashboard
        );

        return $this->render('dashboard/index.html.twig', [
            'user'         => $user,
            'reclamations' => $reclamations,
        ]);
    }
}

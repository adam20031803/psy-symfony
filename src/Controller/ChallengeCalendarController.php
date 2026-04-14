<?php

// src/Controller/ChallengeCalendarController.php

namespace App\Controller;

use App\Repository\ChallengeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/challenge-calendar')]
class ChallengeCalendarController extends AbstractController
{
    #[Route('/', name: 'app_challenge_calendar', methods: ['GET'])]
    public function index(ChallengeRepository $challengeRepository): Response
    {
        $challenges = $challengeRepository->findAll();

        $stats = [
            'total'   => count($challenges),
            'actifs'  => count(array_filter($challenges, fn($c) => $c->getStatut() === 'actif')),
            'termine' => count(array_filter($challenges, fn($c) => $c->getStatut() === 'termine')),
            'annule'  => count(array_filter($challenges, fn($c) => $c->getStatut() === 'annule')),
        ];

        return $this->render('challenge/calendar.html.twig', [
            'stats' => $stats,
        ]);
    }
}

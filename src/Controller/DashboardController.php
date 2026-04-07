<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\ChallengeRepository;
use App\Repository\PostLikeRepository;
use App\Repository\ReclamationRepository;
use App\Repository\HabitudeRepository;
use App\Repository\WorkoutProgressRepository;
use App\Repository\WorkoutPlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        ReclamationRepository $reclamationRepo,
        PostRepository $postRepo,
        ChallengeRepository $challengeRepo,
        PostLikeRepository $postLikeRepo,
        HabitudeRepository $habitudeRepo,
        WorkoutProgressRepository $workoutProgressRepo,
        WorkoutPlanRepository $workoutPlanRepo
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // 5 dernières réclamations
        $reclamations = $reclamationRepo->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        // 3 derniers posts du forum
        $recentPosts = $postRepo->findBy([], ['createdAt' => 'DESC'], 3);

        // 2 challenges actifs
        $activeChallenges = $challengeRepo->findBy(['statut' => 'actif'], ['dateDebut' => 'DESC'], 2);

        // Statistiques utilisateur
        $userStats = [
            'posts_count' => $postRepo->count(['user' => $user]),
            'likes_received' => $postLikeRepo->countTotalLikesOnUserPosts($user),
            'challenges_count' => count($activeChallenges), // On pourrait faire mieux avec une relation de participation
        ];

        // Habitude data
        $habitudeStats = $habitudeRepo->getGlobalStats($user->getId());
        $habitsToday = $habitudeRepo->findCompletedToday($user->getId());

        // Fitness data
        $recentWorkouts = $workoutProgressRepo->findBy(
            ['user' => $user],
            ['date' => 'DESC', 'createdAt' => 'DESC'],
            3
        );
        $plannedWorkouts = $workoutPlanRepo->findBy(
            ['user' => $user, 'statut' => 'planifie'],
            ['datePlanifie' => 'ASC'],
            3
        );

        return $this->render('dashboard/index.html.twig', [
            'user'             => $user,
            'reclamations'     => $reclamations,
            'recentPosts'      => $recentPosts,
            'activeChallenges' => $activeChallenges,
            'userStats'        => $userStats,
            'habitudeStats'    => $habitudeStats,
            'habitsToday'      => $habitsToday,
            'recentWorkouts'   => $recentWorkouts,
            'plannedWorkouts'  => $plannedWorkouts,
        ]);
    }
}

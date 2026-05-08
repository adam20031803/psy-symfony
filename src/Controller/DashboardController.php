<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\ChallengeRepository;
use App\Repository\CommentaireRepository;
use App\Repository\PostLikeRepository;
use App\Repository\ReclamationRepository;
use App\Repository\HabitudeRepository;
use App\Repository\SmartMeetingRepository;
use App\Repository\WorkoutProgressRepository;
use App\Repository\WorkoutPlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        ReclamationRepository $reclamationRepo,
        PostRepository $postRepo,
        ChallengeRepository $challengeRepo,
        CommentaireRepository $commentaireRepo,
        PostLikeRepository $postLikeRepo,
        HabitudeRepository $habitudeRepo,
        WorkoutProgressRepository $workoutProgressRepo,
        WorkoutPlanRepository $workoutPlanRepo,
        SmartMeetingRepository $meetingRepo,
        CacheInterface $cache,
        \App\Service\SmartMeetingTriggerService $triggerService
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // 5 dernières réclamations
        $reclamations = $reclamationRepo->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        // 3 derniers posts du forum (light query to keep memory usage low)
        $recentPosts = $postRepo->findLatestForDashboard(3);
        $postIds = array_values(array_filter(array_map(
            static fn($post): ?int => $post->getId(),
            $recentPosts
        )));
        $postLikeCounts = $postLikeRepo->countLikesForPosts($postIds);
        $postCommentCounts = $commentaireRepo->countForPosts($postIds);

        // 2 challenges actifs
        $activeChallenges = $challengeRepo->findBy(['statut' => 'actif'], ['dateDebut' => 'DESC'], 3);

        // Throttle expensive orchestrator checks: run once every 10 minutes per user.
        $cache->get('dashboard_ai_trigger_user_' . $user->getId(), function (ItemInterface $item) use ($activeChallenges, $triggerService): bool {
            $item->expiresAfter(600);
            foreach ($activeChallenges as $challenge) {
                $triggerService->checkAndTrigger($challenge);
            }

            return true;
        });

        // Fetch all scheduled meetings in one query instead of one query per challenge.
        $aiMeetings = $meetingRepo->findScheduledByChallenges($activeChallenges);

        // Cache per-user stats for short periods to reduce repeated DB work.
        $userStats = $cache->get('dashboard_user_stats_' . $user->getId(), function (ItemInterface $item) use ($postRepo, $postLikeRepo, $user, $activeChallenges): array {
            $item->expiresAfter(120);

            return [
                'posts_count' => $postRepo->count(['user' => $user]),
                'likes_received' => $postLikeRepo->countTotalLikesOnUserPosts($user),
                'challenges_count' => count($activeChallenges),
            ];
        });

        // Habitude data (cached independently from user stats)
        $habitudeStats = $cache->get('dashboard_habitude_stats_' . $user->getId(), function (ItemInterface $item) use ($habitudeRepo, $user): array {
            $item->expiresAfter(300);

            return $habitudeRepo->getGlobalStats($user->getId());
        });
        $habitsTodayCount = $cache->get('dashboard_habits_today_count_' . $user->getId(), function (ItemInterface $item) use ($habitudeRepo, $user): int {
            $item->expiresAfter(60);

            return $habitudeRepo->countCompletedToday($user->getId());
        });

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
            'user' => $user,
            'reclamations' => $reclamations,
            'recentPosts' => $recentPosts,
            'postLikeCounts' => $postLikeCounts,
            'postCommentCounts' => $postCommentCounts,
            'activeChallenges' => $activeChallenges,
            'aiMeetings' => $aiMeetings, // Transmis à la vue
            'userStats' => $userStats,
            'habitudeStats' => $habitudeStats,
            'habitsTodayCount' => $habitsTodayCount,
            'recentWorkouts' => $recentWorkouts,
            'plannedWorkouts' => $plannedWorkouts,
        ]);
    }
}

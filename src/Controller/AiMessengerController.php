<?php
// src/Controller/AiMessengerController.php
// FICHIER À CRÉER

namespace App\Controller;

use App\Entity\AiConversation;
use App\Entity\User;
use App\Repository\AiConversationRepository;
use App\Repository\AiInsightRepository;
use App\Repository\AiUserProfileRepository;
use App\Service\AiMessengerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ai-messenger')]
class AiMessengerController extends AbstractController
{
    public function __construct(
        private AiMessengerService       $messengerService,
        private AiConversationRepository $convRepo,
        private AiUserProfileRepository  $profileRepo,
        private AiInsightRepository      $insightRepo,
        private EntityManagerInterface   $em,
    ) {}

    // ════════════════════════════════════════════════════════
    // PAGE PRINCIPALE DU MESSENGER
    // ════════════════════════════════════════════════════════

    #[Route('/', name: 'app_ai_messenger', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user    = $this->getUser() ?? $this->em->getRepository(User::class)->findOneBy([]);
        $conv    = $this->convRepo->findActiveForUser($user);
        $profile = $this->profileRepo->findOrCreateForUser($user, $this->em);
        $insights = $this->insightRepo->findUnreadForUser($user, 3);

        // Créer la conversation si elle n'existe pas
        if (!$conv) {
            $conv = new AiConversation();
            $conv->setUser($user);
            $this->em->persist($conv);
            $this->em->flush();
            // Message de bienvenue
            $this->messengerService->getWelcomeMessage($user, $conv, $profile);
        }

        $messages = $conv->getMessages();

        return $this->render('ai_messenger/index.html.twig', [
            'conversation' => $conv,
            'messages'     => $messages,
            'profile'      => $profile,
            'insights'     => $insights,
            'user'         => $user,
        ]);
    }

    // ════════════════════════════════════════════════════════
    // AJAX : ENVOYER UN MESSAGE
    // ════════════════════════════════════════════════════════

    #[Route('/send', name: 'app_ai_messenger_send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser() ?? $this->em->getRepository(User::class)->findOneBy([]);

        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message)) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        try {
            $assistantMsg = $this->messengerService->chat($user, $message);
            $profile      = $this->profileRepo->findOrCreateForUser($user, $this->em);

            return $this->json([
                'success' => true,
                'message' => [
                    'id'           => $assistantMsg->getId(),
                    'content'      => $assistantMsg->getContent(),
                    'type'         => $assistantMsg->getMessageType(),
                    'quick_replies' => $assistantMsg->getQuickReplies(),
                    'created_at'   => $assistantMsg->getCreatedAt()->format('H:i'),
                ],
                'profile' => [
                    'xp'            => $profile->getTotalXp(),
                    'level'         => $profile->getLevel(),
                    'level_name'    => $profile->getLevelName(),
                    'streak'        => $profile->getStreakDays(),
                    'level_progress' => $profile->getLevelProgress(),
                    'badges'        => array_slice($profile->getBadges(), -3), // 3 derniers
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur IA : ' . $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════
    // AJAX : CHANGER DE PHASE
    // ════════════════════════════════════════════════════════

    #[Route('/set-phase/{phase}', name: 'app_ai_messenger_phase', methods: ['POST'])]
    public function setPhase(string $phase): JsonResponse
    {
        $validPhases = [
            AiConversation::PHASE_DAILY,
            AiConversation::PHASE_MOTIVATION,
            AiConversation::PHASE_CRISIS,
            AiConversation::PHASE_COACHING,
            AiConversation::PHASE_REFLECTION,
        ];

        if (!in_array($phase, $validPhases)) {
            return $this->json(['error' => 'Phase invalide'], 400);
        }

        /** @var User $user */
        $user = $this->getUser() ?? $this->em->getRepository(User::class)->findOneBy([]);
        $conv = $this->convRepo->findActiveForUser($user);

        if ($conv) {
            $conv->setPhase($phase);
            
            // Envoyer un message de transition
            $transitionTexts = [
                AiConversation::PHASE_MOTIVATION  => "Prêt à booster ta motivation ! Dis-moi ce qui te bloque en ce moment 🔥",
                AiConversation::PHASE_CRISIS      => "Je suis là pour toi. Parle-moi, rien ne presse. Que se passe-t-il vraiment ? 💙",
                AiConversation::PHASE_COACHING    => "Mode coaching activé ! Quel objectif on attaque aujourd'hui ? 🎯",
                AiConversation::PHASE_REFLECTION  => "Prenons du recul ensemble. Qu'est-ce qui a bien marché cette semaine pour toi ? ✨",
                AiConversation::PHASE_DAILY       => "Check-in du jour ! Sur une échelle de 1 à 10, tu en es où en ce moment ? 📊",
            ];

            $text = $transitionTexts[$phase] ?? 'Phase changée !';

            $msg = new \App\Entity\AiMessage();
            $msg->setConversation($conv);
            $msg->setRole(\App\Entity\AiMessage::ROLE_ASSISTANT);
            $msg->setContent($text);
            $msg->setMessageType(\App\Entity\AiMessage::TYPE_TEXT);
            $this->em->persist($msg);
            $conv->incrementMessages();

            $this->em->flush();

            return $this->json([
                'success' => true,
                'phase'   => $phase,
                'message' => [
                    'id'           => $msg->getId(),
                    'content'      => $msg->getContent(),
                    'type'         => $msg->getMessageType(),
                    'created_at'   => $msg->getCreatedAt()->format('H:i'),
                ],
            ]);
        }

        return $this->json(['error' => 'Conversation non trouvée'], 404);
    }

    // ════════════════════════════════════════════════════════
    // AJAX : MARQUER INSIGHTS COMME LUS
    // ════════════════════════════════════════════════════════

    #[Route('/insights/read/{id}', name: 'app_ai_insight_read', methods: ['POST'])]
    public function markInsightRead(int $id): JsonResponse
    {
        $insight = $this->insightRepo->find($id);
        if ($insight) {
            $insight->setIsRead(true);
            $this->em->flush();
        }
        return $this->json(['success' => true]);
    }

    // ════════════════════════════════════════════════════════
    // AJAX : RESET CONVERSATION (nouvelle session)
    // ════════════════════════════════════════════════════════

    #[Route('/reset', name: 'app_ai_messenger_reset', methods: ['POST'])]
    public function reset(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser() ?? $this->em->getRepository(User::class)->findOneBy([]);
        $conv = $this->convRepo->findActiveForUser($user);

        if ($conv) {
            // Supprimer les messages mais garder le profil
            foreach ($conv->getMessages() as $msg) {
                $this->em->remove($msg);
            }
            $this->em->remove($conv);
            $this->em->flush();
        }

        return $this->json(['success' => true, 'redirect' => $this->generateUrl('app_ai_messenger')]);
    }

    // ════════════════════════════════════════════════════════
    // AJAX : PROFIL COMPLET UTILISATEUR
    // ════════════════════════════════════════════════════════

    #[Route('/profile', name: 'app_ai_messenger_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user    = $this->getUser() ?? $this->em->getRepository(User::class)->findOneBy([]);
        $profile = $this->profileRepo->findOrCreateForUser($user, $this->em);
        $conv    = $this->convRepo->findActiveForUser($user);

        return $this->json([
            'user' => [
                'prenom'    => $user->getPrenom(),
                'nom'       => $user->getNom(),
                'email'     => $user->getEmail(),
            ],
            'profile' => [
                'level'              => $profile->getLevel(),
                'level_name'         => $profile->getLevelName(),
                'total_xp'           => $profile->getTotalXp(),
                'level_progress'     => $profile->getLevelProgress(),
                'streak_days'        => $profile->getStreakDays(),
                'badges'             => $profile->getBadges(),
                'wake_up_time'       => $profile->getWakeUpTime(),
                'sleep_time'         => $profile->getSleepTime(),
                'exercise_frequency' => $profile->getExerciseFrequency(),
                'work_schedule'      => $profile->getWorkSchedule(),
                'stress_triggers'    => $profile->getStressTriggers(),
                'motivation_drivers' => $profile->getMotivationDrivers(),
                'onboarding_done'    => $profile->isOnboardingComplete(),
            ],
            'conversation' => $conv ? [
                'phase'            => $conv->getPhase(),
                'phase_label'      => $conv->getPhaseLabel(),
                'personality_type' => $conv->getPersonalityType(),
                'goals'            => $conv->getGoals(),
                'blockers'         => $conv->getBlockers(),
                'strengths'        => $conv->getStrengths(),
                'habits'           => $conv->getHabits(),
                'total_messages'   => $conv->getTotalMessages(),
            ] : null,
        ]);
    }
}
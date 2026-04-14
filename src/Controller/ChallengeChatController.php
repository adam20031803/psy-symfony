<?php

namespace App\Controller;

use App\Entity\ChallengeChat;
use App\Entity\SmartMeeting;
use App\Entity\User;
use App\Repository\ChallengeChatRepository;
use App\Repository\ChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/challenge-chat')]
#[IsGranted('ROLE_USER')]
class ChallengeChatController extends AbstractController
{
    #[Route('/{id}/messages', name: 'app_challenge_chat_messages', methods: ['GET'])]
    public function messages(
        int $id,
        ChallengeRepository $challengeRepo,
        ChallengeChatRepository $chatRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $challenge = $challengeRepo->find($id);
        if (!$challenge) {
            return $this->json(['error' => 'Challenge not found'], 404);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $chats = $chatRepo->findBy(
            ['challenge' => $challenge],
            ['createdAt' => 'ASC'],
            100
        );

        $messages = array_map(function (ChallengeChat $c) use ($currentUser) {
            $user = $c->getUser();
            return [
                'id'        => $c->getId(),
                'message'   => $c->getMessage(),
                'createdAt' => $c->getCreatedAt()->format('H:i'),
                'date'      => $c->getCreatedAt()->format('d/m/Y'),
                'userId'    => $user->getId(),
                'userName'  => $user->getPrenom() . ' ' . $user->getNom(),
                'userRole'  => $user->getRole(),
                'initials'  => strtoupper(
                    mb_substr($user->getPrenom() ?? '', 0, 1) .
                    mb_substr($user->getNom() ?? '', 0, 1)
                ),
                'isMe'      => $user->getId() === $currentUser?->getId(),
            ];
        }, $chats);

        $meetings     = $em->getRepository(SmartMeeting::class)->findBy(
            ['challenge' => $challenge, 'status' => 'SCHEDULED'],
            ['createdAt' => 'DESC']
        );
        $meetingsData = array_map(fn(SmartMeeting $m) => [
            'id'            => $m->getId(),
            'type'          => $m->getType(),
            'triggerReason' => $m->getTriggerReason(),
            'meetingUrl'    => $m->getMeetingUrl(),
            'createdAt'     => $m->getCreatedAt()->format('d/m/Y à H:i'),
            'scheduledFor'  => $m->getAiContext()['scheduled_for'] ?? null,
        ], $meetings);

        return $this->json([
            'messages' => $messages,
            'meetings' => $meetingsData,
        ]);
    }

    #[Route('/{id}/send', name: 'app_challenge_chat_send', methods: ['POST'])]
    public function send(
        int $id,
        Request $request,
        ChallengeRepository $challengeRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $challenge = $challengeRepo->find($id);
        if (!$challenge) {
            return $this->json(['error' => 'Challenge not found'], 404);
        }

        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message) || mb_strlen($message) > 2000) {
            return $this->json(['error' => 'Message invalide (1-2000 caractères)'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        $chat = new ChallengeChat();
        $chat->setChallenge($challenge);
        $chat->setUser($user);
        $chat->setMessage($message);
        $em->persist($chat);
        $em->flush();

        return $this->json([
            'success'   => true,
            'id'        => $chat->getId(),
            'message'   => $chat->getMessage(),
            'createdAt' => $chat->getCreatedAt()->format('H:i'),
            'date'      => $chat->getCreatedAt()->format('d/m/Y'),
            'userId'    => $user->getId(),
            'userName'  => $user->getPrenom() . ' ' . $user->getNom(),
            'userRole'  => $user->getRole(),
            'initials'  => strtoupper(
                mb_substr($user->getPrenom() ?? '', 0, 1) .
                mb_substr($user->getNom() ?? '', 0, 1)
            ),
            'isMe'      => true,
        ]);
    }

    #[Route('/{id}/schedule-meeting', name: 'app_challenge_schedule_meeting', methods: ['POST'])]
    public function scheduleMeeting(
        int $id,
        Request $request,
        ChallengeRepository $challengeRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $challenge = $challengeRepo->find($id);
        if (!$challenge) {
            return $this->json(['error' => 'Challenge not found'], 404);
        }

        /** @var User $admin */
        $admin = $this->getUser();

        $data         = json_decode($request->getContent(), true);
        $scheduledFor = $data['scheduled_for'] ?? null;
        $meetingType  = $data['meeting_type']  ?? 'GROUP_SYNC';
        $reason       = $data['reason']        ?? "Réunion planifiée par l'administrateur";

        if (!$scheduledFor) {
            return $this->json(['error' => 'Date de réunion requise'], 400);
        }

        // Générer un lien de réunion valide (Jitsi Meet)
        $meetUrl = $this->generateJitsiMeetLink();

        $meeting = new SmartMeeting();
        $meeting->setChallenge($challenge);
        $meeting->setType($meetingType);
        $meeting->setTriggerReason($reason);
        $meeting->setMeetingUrl($meetUrl);
        $meeting->setStatus('SCHEDULED');
        $meeting->setAiContext([
            'scheduled_for' => $scheduledFor,
            'scheduled_by'  => $admin->getPrenom() . ' ' . $admin->getNom(),
            'meeting_type'  => $meetingType,
            'ai_suggestion' => $this->getAiMeetingSuggestion($meetingType, $challenge->getTitre()),
        ]);
        $em->persist($meeting);

        $scheduledDate = (new \DateTime($scheduledFor))->format('d/m/Y à H:i');
        $systemMsg     = new ChallengeChat();
        $systemMsg->setChallenge($challenge);
        $systemMsg->setUser($admin);
        $systemMsg->setMessage(
            "🤖 [IA ORCHESTRATEUR] Une réunion a été planifiée pour le {$scheduledDate}. Rejoignez via : {$meetUrl}"
        );
        $em->persist($systemMsg);
        $em->flush();

        return $this->json([
            'success'      => true,
            'meetingId'    => $meeting->getId(),
            'meetingUrl'   => $meetUrl,
            'scheduledFor' => $scheduledFor,
            'type'         => $meetingType,
            'aiSuggestion' => $meeting->getAiContext()['ai_suggestion'],
        ]);
    }

    #[Route('/meeting/{meetingId}/cancel', name: 'app_challenge_cancel_meeting', methods: ['POST'])]
    public function cancelMeeting(int $meetingId, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $meeting = $em->getRepository(SmartMeeting::class)->find($meetingId);
        if (!$meeting) {
            return $this->json(['error' => 'Réunion introuvable'], 404);
        }

        $meeting->setStatus('CANCELLED');
        $em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Génère un lien Jitsi Meet valide et fonctionnel
     * Jitsi est gratuit, open-source, et ne nécessite pas d'API
     */
    private function generateJitsiMeetLink(): string
    {
        // Génère un ID de salle unique basé sur timestamp et random
        $timestamp = base_convert(time(), 10, 36);
        $random = bin2hex(random_bytes(8));
        $roomId = "challenge-meet-{$timestamp}-{$random}";
        
        return "https://meet.jit.si/{$roomId}";
    }

    /**
     * Alternative: Génère un lien Google Meet avec un format correct
     * Note: Les liens Google Meet doivent être créés via l'API Google Calendar
     * Cette méthode génère un format valide mais peut ne pas fonctionner si le code n'existe pas
     */
    private function generateGoogleMeetLink(): string
    {
        // Génère un code au format Google Meet: xxx-yyyy-zzz
        $segments = [];
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        
        for ($i = 0; $i < 3; $i++) {
            $segment = '';
            $length = $i === 1 ? 4 : 3;
            for ($j = 0; $j < $length; $j++) {
                $segment .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $segments[] = $segment;
        }
        
        $meetCode = implode('-', $segments);
        
        // Note: Cette URL peut ne pas fonctionner car le code doit exister sur Google
        // Pour une solution fiable, utilisez generateJitsiMeetLink() à la place
        return "https://meet.google.com/{$meetCode}";
    }

    private function getAiMeetingSuggestion(string $type, string $challengeTitle): string
    {
        $suggestions = [
            'GROUP_SYNC'    => "Pour \"{$challengeTitle}\" : tour de table 5 min, puis sous-groupes sur les tâches bloquées. Utilisez le chat pour partager vos notes.",
            'FLASH_SYNC'    => "Flash 15 min sur \"{$challengeTitle}\" : avancement / blocages / actions 48h. Soyez concis et précis.",
            'DEBUG'         => "Déblocage \"{$challengeTitle}\" : listez les obstacles, assignez un responsable par problème. Priorisez les solutions.",
            'RETROSPECTIVE' => "Rétro \"{$challengeTitle}\" : Start/Stop/Continue — 45 minutes recommandées. Préparez vos feedbacks à l'avance.",
        ];

        return $suggestions[$type] ?? "Réunion planifiée pour le challenge \"{$challengeTitle}\". Préparez vos points à discuter.";
    }
}
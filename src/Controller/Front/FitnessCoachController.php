<?php

namespace App\Controller\Front;

use App\Service\FitnessProgramGeneratorService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fitness/coach', name: 'fitness_coach_')]
class FitnessCoachController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        return $this->render('front/coach/index.html.twig', [
            'userEmail' => $user?->getEmail() ?? '',
            'userName'  => ($user?->getPrenom() ?? '') ?: ($user?->getNom() ?? 'Utilisateur'),
        ]);
    }

    #[Route('/generate', name: 'generate', methods: ['POST'])]
    public function generate(
        Request $request,
        FitnessProgramGeneratorService $generator,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Fallback: try form data if JSON fails
        if (!$data) {
            $data = $request->request->all();
        }

        $goal  = trim($data['goal'] ?? '');
        $level = trim($data['level'] ?? 'Débutant');
        $days  = (int)($data['days'] ?? 3);

        // Always use the logged-in user's email & name — never trust client input
        /** @var \App\Entity\User|null $user */
        $user  = $this->getUser();
        $email = $user?->getEmail() ?? trim($data['email'] ?? '');
        $name  = ($user?->getPrenom() ?? '') ?: ($user?->getNom() ?? 'Utilisateur');

        if (!$goal) {
            return new JsonResponse(['error' => 'Objectif manquant. Recommence la conversation.'], 400);
        }
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Email introuvable. Assure-toi d\'être connecté.'], 400);
        }

        // Generate program via Gemini
        try {
            $program = $generator->generateProgram([
                'goal'  => $goal,
                'level' => $level,
                'days'  => $days,
            ]);

            if (!$program) {
                return new JsonResponse([
                    'error' => 'Aucun modèle n’a pu générer un programme valide. Vérifie qu’Ollama est démarré (ollama serve), que les modèles sont installés, puis réessaie. Si le problème continue, les clés Gemini / Groq peuvent être manquantes ou le quota atteint.',
                ], 400);
            }
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Erreur technique : ' . $e->getMessage()], 500);
        }

        // Build exercises list for the response & email
        $exercises = [];
        foreach ($program->getExercises() as $ex) {
            $exercises[] = [
                'name'        => $ex->getName(),
                'description' => $ex->getDescription(),
                'category'    => $ex->getCategory(),
                'duration'    => $ex->getDuration(),
                'calories'    => $ex->getCalories(),
            ];
        }

        $programData = [
            'title'         => $program->getTitle(),
            'goal'          => $program->getGoal(),
            'level'         => $program->getLevel(),
            'durationWeeks' => $program->getDurationWeeks(),
            'exercises'     => $exercises,
        ];

        // Send email
        try {
            $emailMsg = (new TemplatedEmail())
                ->from(new Address('noreply@atomicyou.app', 'Atomic You — AI Coach'))
                ->to($email)
                ->subject('🏋️ Ton programme fitness personnalisé est prêt !')
                ->htmlTemplate('emails/fitness_program.html.twig')
                ->context([
                    'name'    => $name,
                    'program' => $programData,
                ]);
            $mailer->send($emailMsg);
            $emailSent = true;
        } catch (\Throwable $e) {
            $emailSent = false;
        }

        return new JsonResponse([
            'success'   => true,
            'emailSent' => $emailSent,
            'program'   => $programData,
        ]);
    }
}

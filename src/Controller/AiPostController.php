<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\CategorieRepository;
use App\Service\BadWordChecker;
use App\Service\GeminiService;
use App\Service\GroqService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class AiPostController extends AbstractController
{
    #[Route('/posts/ai-generate', name: 'app_posts_ai_generate', methods: ['POST'])]
    public function generate(
        Request $request,
        GeminiService $gemini,
        GroqService $groq,
        CategorieRepository $categorieRepo,
        BadWordChecker $badWordChecker
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $subject = $data['subject'] ?? '';

        if (empty($subject)) {
            return new JsonResponse(['success' => false, 'error' => 'Veuillez saisir un sujet ou une idée.'], 400);
        }

        $categories = $categorieRepo->findAll();
        $catList = [];
        foreach ($categories as $cat) {
            $catList[] = "ID: " . $cat->getId() . " - Nom: " . $cat->getNom();
        }
        $catString = implode(", ", $catList);

        $prompt = <<<PROMPT
Tu es un rédacteur web expert pour l'application "Atomic You", une plateforme dédiée au bien-être et au développement personnel.
Génère un post engageant.

Sujet : "{$subject}"
Catégories : [{$catString}]

Réponds UNIQUEMENT au format JSON :
{
  "title": "Titre percutant",
  "content": "Contenu structuré...",
  "category_id": ID
}
PROMPT;

        $response = $gemini->generateResponse($prompt);
        $result = $this->extractJson($response);

        if (!$result || !isset($result['title'], $result['content'])) {
            $response = $groq->generateResponse($prompt);
            $result = $this->extractJson($response);
        }

        if (!$result) {
            return new JsonResponse(['success' => false, 'error' => "L'IA n'a pas pu générer le contenu."], 500);
        }

        return new JsonResponse([
            'success'     => true,
            'title'       => $result['title'],
            'content'     => $result['content'],
            'category_id' => $result['category_id'] ?? null
        ]);
    }

    #[Route('/posts/{id}/translate', name: 'app_posts_ai_translate', methods: ['POST'])]
    public function translate(Post $post, GeminiService $gemini): JsonResponse
    {
        $prompt = "Translate the following French post to English. Return ONLY JSON: {\"title\": \"...\", \"content\": \"...\"}\nTitle: {$post->getTitre()}\nContent: {$post->getContenu()}";
        
        $response = $gemini->generateResponse($prompt);
        $result = $this->extractJson($response);

        if (!$result) {
            return new JsonResponse(['success' => false, 'error' => 'Traduction impossible.'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'title'   => $result['title'],
            'content' => $result['content'],
        ]);
    }

    private function extractJson(string $text): ?array
    {
        // Enlève les backticks markdown et le texte superflu
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
        
        // Si le JSON est au milieu d'un texte, on tente de le trouver
        if (preg_match('/\{.*\}/s', $clean, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        return json_decode($clean, true);
    }
}

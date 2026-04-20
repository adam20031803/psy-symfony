<?php

// src/Controller/Api/AiPostController.php

namespace App\Controller\Api;

use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/post', name: 'api_post_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class AiPostController extends AbstractController
{
    public function __construct(private readonly GeminiService $gemini) {}

    /* ════════════════════════════════════════════════════════════════
       GÉNÉRER — Titre + Contenu depuis un sujet
    ════════════════════════════════════════════════════════════════ */
    #[Route('/generate', name: 'generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        $data  = json_decode($request->getContent(), true);
        $topic = trim($data['topic'] ?? '');

        if (empty($topic)) {
            return $this->json(['error' => 'Sujet manquant.'], 400);
        }

        $prompt = <<<PROMPT
Tu es un rédacteur expert pour une plateforme de bien-être mental nommée "Atomic You".
Génère un post de forum engageant en FRANÇAIS sur ce sujet : "{$topic}".

Retourne UNIQUEMENT un JSON valide avec ce format exact (sans markdown, sans explication) :
{
  "titre": "...",
  "contenu": "..."
}

Le titre doit être accrocheur (max 80 caractères).
Le contenu doit être inspirant, bienveillant, structuré en 2-3 paragraphes (200-350 mots).
PROMPT;

        try {
            $raw      = $this->gemini->generateResponse($prompt);
            // Nettoyer les blocs markdown éventuels
            $clean    = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
            $clean    = preg_replace('/\s*```$/', '', $clean);
            $decoded  = json_decode($clean, true);

            if (!$decoded || !isset($decoded['titre'], $decoded['contenu'])) {
                return $this->json(['error' => 'Réponse IA invalide. Réessayez.'], 500);
            }

            return $this->json([
                'titre'   => $decoded['titre'],
                'contenu' => $decoded['contenu'],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur IA : ' . $e->getMessage()], 500);
        }
    }

    /* ════════════════════════════════════════════════════════════════
       TRADUIRE — Tout contenu → Français
    ════════════════════════════════════════════════════════════════ */
    #[Route('/translate', name: 'translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        $data    = json_decode($request->getContent(), true);
        $titre   = trim($data['titre']   ?? '');
        $contenu = trim($data['contenu'] ?? '');

        if (empty($titre) && empty($contenu)) {
            return $this->json(['error' => 'Aucun contenu à traduire.'], 400);
        }

        $toTranslate = '';
        if ($titre)   $toTranslate .= "TITRE: {$titre}\n";
        if ($contenu) $toTranslate .= "CONTENU: {$contenu}";

        $prompt = <<<PROMPT
Détecte la langue du texte suivant et traduis-le INTÉGRALEMENT en Français.
Garde le style, le ton et la mise en forme d'origine.

Retourne UNIQUEMENT un JSON valide (sans markdown, sans explication) :
{
  "titre": "...",
  "contenu": "..."
}

Si le titre ou le contenu est vide, retourne une chaîne vide "" pour ce champ.

Texte à traduire :
{$toTranslate}
PROMPT;

        try {
            $raw     = $this->gemini->generateResponse($prompt);
            $clean   = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
            $clean   = preg_replace('/\s*```$/', '', $clean);
            $decoded = json_decode($clean, true);

            if (!$decoded || !array_key_exists('titre', $decoded) || !array_key_exists('contenu', $decoded)) {
                return $this->json(['error' => 'Réponse IA invalide. Réessayez.'], 500);
            }

            return $this->json([
                'titre'   => $decoded['titre'],
                'contenu' => $decoded['contenu'],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur IA : ' . $e->getMessage()], 500);
        }
    }
}

<?php
// src/Service/AiMessengerService.php
// FICHIER À CRÉER — Le cerveau du messenger IA

namespace App\Service;

use App\Entity\AiConversation;
use App\Entity\AiInsight;
use App\Entity\AiMessage;
use App\Entity\AiUserProfile;
use App\Entity\User;
use App\Repository\AiConversationRepository;
use App\Repository\AiUserProfileRepository;
use Doctrine\ORM\EntityManagerInterface;

class AiMessengerService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AiConversationRepository $convRepo,
        private readonly AiUserProfileRepository $profileRepo,
        private readonly string $geminiApiKey,
    ) {
    }

    // ════════════════════════════════════════════════════════
    // POINT D'ENTRÉE PRINCIPAL
    // ════════════════════════════════════════════════════════

    /**
     * Traite un message utilisateur et génère la réponse IA.
     * Retourne le message assistant créé.
     */
    public function chat(User $user, string $userText): AiMessage
    {
        // 1. Récupérer ou créer la conversation
        $conv = $this->convRepo->findActiveForUser($user);
        if (!$conv) {
            $conv = new AiConversation();
            $conv->setUser($user);
            $this->em->persist($conv);
        }
        $conv->touchActivity();

        // 2. Récupérer ou créer le profil
        $profile = $this->profileRepo->findOrCreateForUser($user, $this->em);

        // 3. Sauvegarder le message utilisateur
        $userMsg = new AiMessage();
        $userMsg->setConversation($conv);
        $userMsg->setRole(AiMessage::ROLE_USER);
        $userMsg->setContent($userText);
        $userMsg->setMessageType(AiMessage::TYPE_TEXT);
        $this->em->persist($userMsg);
        $conv->incrementMessages();

        // 4. Analyser l'émotion et extraire les données
        $emotion = $this->detectEmotion($userText);
        $extracted = $this->extractDataFromMessage($userText, $conv);
        $userMsg->setEmotionDetected($emotion);
        if ($extracted) {
            $userMsg->setDataCollected($extracted);
            $this->updateProfile($conv, $profile, $extracted);
        }

        // 5. Générer la réponse IA via Gemini
        $history = $this->buildHistory($conv);
        $systemPrompt = $this->buildSystemPrompt($conv, $profile, $user);
        $aiResponse = $this->callGemini($systemPrompt, $history, $userText);

        // 6. Parser la réponse IA (JSON structuré)
        $parsed = $this->parseAiResponse($aiResponse);

        // 7. Créer le message assistant
        $assistantMsg = new AiMessage();
        $assistantMsg->setConversation($conv);
        $assistantMsg->setRole(AiMessage::ROLE_ASSISTANT);
        $assistantMsg->setContent($parsed['text']);
        $assistantMsg->setMessageType($parsed['type'] ?? AiMessage::TYPE_TEXT);
        if (!empty($parsed['quick_replies'])) {
            $assistantMsg->setQuickReplies($parsed['quick_replies']);
        }
        $this->em->persist($assistantMsg);
        $conv->incrementMessages();

        // 8. Mettre à jour le profil si IA a détecté des infos
        if (!empty($parsed['data_extracted'])) {
            $this->updateProfile($conv, $profile, $parsed['data_extracted']);
        }

        // 9. Générer un insight si approprié
        if (!empty($parsed['insight'])) {
            $this->createInsight($user, $parsed['insight']);
        }

        // 10. Gamification : XP + streak
        $this->applyGamification($profile, $parsed['xp_reward'] ?? 5);

        // 11. Avancer la phase si nécessaire
        if ($conv->shouldAdvancePhase()) {
            $conv->setPhase(AiConversation::PHASE_DAILY);
            $profile->setOnboardingComplete(true);
        }

        // 12. Mettre à jour le mood si disponible
        if (!empty($parsed['mood_score'])) {
            $conv->setMoodScore((int) $parsed['mood_score']);
        }

        $profile->touch();
        $this->em->flush();

        return $assistantMsg;
    }

    // ════════════════════════════════════════════════════════
    // SYSTÈME PROMPT — LE CŒUR DE LA PERSONNALITÉ IA
    // ════════════════════════════════════════════════════════

    private function buildSystemPrompt(AiConversation $conv, AiUserProfile $profile, User $user): string
    {
        $prenom      = $user->getPrenom() ?: 'ami';
        $phase       = $conv->getPhase();
        $personality = $conv->getPersonalityType() ?? 'unknown';
        $style       = $conv->getCommunicationStyle() ?? 'gentle';
        $streak      = $profile->getStreakDays();
        $level       = $profile->getLevelName();
        $msgCount    = $conv->getTotalMessages();

        $knownData = $this->summarizeKnownData($conv, $profile);

        // Count what coaching data we already have
        $hasGoals    = count($conv->getGoals()) > 0;
        $hasBlockers = count($conv->getBlockers()) > 0;
        $hasHabits   = count($conv->getHabits()) > 0;
        $collectedCount = ($hasGoals ? 1 : 0) + ($hasBlockers ? 1 : 0) + ($hasHabits ? 1 : 0)
            + ($profile->getWakeUpTime() ? 1 : 0) + ($profile->getExerciseFrequency() ? 1 : 0);

        $phaseInstructions = match ($phase) {
            AiConversation::PHASE_ONBOARDING => "
Tu es en PHASE D'ONBOARDING. Tu dois :
1. Accueillir {$prenom} chaleureusement
2. Poser UNE SEULE question à la fois pour mieux le connaître
3. Explorer : son quotidien, ses habitudes, ses rêves, ses blocages, son heure de réveil, comment il gère le stress
4. Mémoriser TOUT ce qu'il dit pour personnaliser chaque future interaction
5. Après 3 échanges, détecter son type de personnalité (achiever/dreamer/analyst/empath)
6. NE JAMAIS répéter la même question que dans le message précédent — regarde l'historique
",
            AiConversation::PHASE_DAILY => "
Tu es en PHASE CHECK-IN QUOTIDIEN. Tu dois :
1. Demander comment se sent {$prenom} aujourd'hui (score humeur 1-10)
2. Faire un bilan rapide de la journée/nuit
3. Identifier si quelque chose le bloque
4. Proposer une action concrète basée sur son profil connu
5. Le motiver avec sa propre façon d'être motivé
6. NE JAMAIS répéter une question déjà posée dans cette conversation
",
            AiConversation::PHASE_COACHING => $this->buildCoachingInstructions(
                $prenom, $knownData, $collectedCount, $hasGoals, $hasBlockers
            ),
            AiConversation::PHASE_MOTIVATION => "
Tu es en MODE MOTIVATION INTENSE. Tu dois :
1. Utiliser le style de communication qui correspond à {$prenom} : {$style}
2. Rappeler ses propres objectifs et rêves qu'il t'a confiés : {$knownData}
3. Utiliser des métaphores puissantes et personnalisées à sa situation réelle
4. Finir par UNE action précise et ultra-concrète à faire dans les 10 prochaines minutes
5. Créer de l'urgence positive, pas de la culpabilité
6. NE PAS répéter ce qui a déjà été dit — trouve un angle nouveau et percutant
",
            AiConversation::PHASE_CRISIS => "
Tu es en MODE SOUTIEN EMPATHIQUE. {$prenom} traverse une période difficile. Tu dois :
1. Valider ses émotions SANS minimiser — «c'est normal de ressentir ça»
2. Écouter activement avant de conseiller
3. Poser des questions ouvertes douces, jamais deux fois la même
4. Proposer des micro-actions ultra-simples (2 min max)
5. Rester présent, ne pas forcer de solutions
",
            AiConversation::PHASE_REFLECTION => "
Tu es en MODE RÉFLEXION/BILAN. Tu dois :
1. Aider {$prenom} à prendre du recul sur sa semaine/mois
2. Identifier les patterns positifs et négatifs dans ses habitudes : {$knownData}
3. Célébrer les progrès (même minimes) — il a {$streak} jours de streak !
4. Aider à définir les prochains objectifs SMART
5. Créer un 'moment de clarté' qui l'inspire pour la suite
6. NE JAMAIS poser la même question que dans le message précédent
",
            default => "Tu es en mode coaching actif. Pose une question pertinente et personnalisée."
        };

        return "Tu es ARIA — l'IA coach personnel ultra-personnalisée de la plateforme Atomic You.

IDENTITÉ D'ARIA :
- Tu es empathique, perspicace, légèrement humoristique selon le contexte
- Tu parles TOUJOURS en français, tu tutoies l'utilisateur
- Tu as une mémoire parfaite : tu te souviens de TOUT ce que {$prenom} t'a dit
- Tu adaptes ton style à sa personnalité détectée : {$personality}
- Tu es directe, sans blabla — chaque message a de la valeur
- Tu utilises des emojis avec parcimonie (1-2 max par message), jamais en excès
- Tu n'es JAMAIS générique — chaque réponse est unique et personnalisée
- RÈGLE ANTI-RÉPÉTITION : Tu NE DOIS JAMAIS poser la même question que dans le message précédent de la conversation

PROFIL DE {$prenom} (ce que tu sais déjà) :
{$knownData}

Niveau actuel : {$level} | Streak : {$streak} jours | Messages échangés : {$msgCount}

{$phaseInstructions}

STYLE DE COMMUNICATION ({$style}) :
" . $this->getStyleGuide($style) . "

FORMAT DE RÉPONSE (JSON STRICT) :
Tu dois répondre UNIQUEMENT avec un JSON valide, sans aucun texte autour :
{
  \"text\": \"Ton message à {$prenom} (1-4 phrases max, percutant)\",
  \"type\": \"text|question|tip|insight|challenge|celebration|mood_check\",
  \"quick_replies\": [\"Option 1\", \"Option 2\", \"Option 3\"],
  \"mood_score\": null ou int 1-10 si détecté,
  \"data_extracted\": {
    \"wake_up_time\": null,
    \"sleep_time\": null,
    \"exercise_frequency\": null,
    \"stress_trigger\": null,
    \"goal\": null,
    \"blocker\": null,
    \"strength\": null,
    \"value\": null,
    \"achievement\": null,
    \"work_schedule\": null,
    \"habit\": null,
    \"motivation_driver\": null
  },
  \"insight\": null ou {\"type\": \"pattern|recommendation|celebration|warning\", \"title\": \"...\", \"content\": \"...\", \"action\": \"...\"},
  \"xp_reward\": 5
}

RÈGLES ABSOLUES :
- quick_replies : 2-4 options MAX, courtes (3-6 mots), UNIQUEMENT si ça aide la conversation
- Si l'utilisateur semble en détresse, type = 'mood_check' et passe en phase crisis
- Ne JAMAIS répéter la même question deux fois dans la même conversation — consulte l'historique
- Extraire les données avec précision dans data_extracted
- xp_reward entre 5 et 25 selon la richesse de la réponse de l'utilisateur
";
    }

    /**
     * Builds a structured coaching arc that collects data first, then proposes a challenge.
     */
    private function buildCoachingInstructions(
        string $prenom,
        string $knownData,
        int $collectedCount,
        bool $hasGoals,
        bool $hasBlockers
    ): string {
        // Stage 1: Not enough data yet — ask targeted questions
        if ($collectedCount < 3) {
            $questionsNeeded = [];
            if (!$hasGoals)    $questionsNeeded[] = "son objectif principal en ce moment (1 chose concrète)";
            if (!$hasBlockers) $questionsNeeded[] = "ce qui le bloque le plus souvent";
            if ($collectedCount < 2) $questionsNeeded[] = "son niveau d'énergie actuel sur 10 et pourquoi";

            $nextQuestion = $questionsNeeded[0] ?? "ce qui l'a rendu fier de lui récemment";

            return "
Tu es en MODE COACHING ACTIF — PHASE DE COLLECTE DE DONNÉES (étape " . ($collectedCount + 1) . "/3).

Tu dois d'abord bien connaître {$prenom} avant de lui proposer un challenge. Pour ça :
1. Pose UNE SEULE question ciblée sur : {$nextQuestion}
2. Sois chaleureux et montre que tu veux vraiment l'aider, pas juste 'cocher des cases'
3. Base-toi sur ce que tu sais déjà : {$knownData}
4. NE PAS encore proposer de challenge — il faut d'abord comprendre sa situation
5. Extraire et sauvegarder TOUT ce qu'il dit dans data_extracted (goal, blocker, habit, motivation_driver...)
6. Si il répond vaguement, reformule avec empathie pour obtenir plus de précision

QUESTION PRIORITAIRE À POSER : {$nextQuestion}

IMPORTANT: Varie ta formulation, ne commence pas par 'Je t'entends' si c'était dans le message précédent.
";
        }

        // Stage 2: Enough data — propose a personalized challenge
        return "
Tu es en MODE COACHING ACTIF — PHASE DE PROPOSITION DE CHALLENGE.

Tu as maintenant assez d'informations sur {$prenom} :
{$knownData}

Tu dois MAINTENANT :
1. Faire un mini-résumé empathique de ce que tu comprends de sa situation (1-2 phrases)
2. Proposer UN challenge ultra-personnalisé et concret basé sur ses vrais objectifs et blocages :
   - Titre accrocheur et motivant
   - Durée réaliste (7-21 jours)
   - 1 action quotidienne précise (max 15 min/jour)
   - Pourquoi CE challenge va l'aider spécifiquement LUI
3. Expliquer en quoi ce challenge va l'aider à surmonter ses blocages spécifiques
4. Terminer par une phrase de motivation PUISSANTE et PERSONNALISÉE à sa situation

Pour les quick_replies : [\"J'accepte ce challenge 🔥\", \"Trop ambitieux, adaptons\", \"Propose autre chose\"]

Type = 'challenge' pour le message.
Crée aussi un insight de type 'recommendation' avec le détail du challenge.
";
    }

    // ════════════════════════════════════════════════════════
    // APPEL GEMINI API
    // ════════════════════════════════════════════════════════

    private function callGemini(string $systemPrompt, array $history, string $newMessage): string
    {
        if (empty($this->geminiApiKey)) {
            return $this->getFallbackResponse($newMessage);
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->geminiApiKey;

        // Construire les contents Gemini (format multi-tour)
        $contents = [];

        // Ajouter l'historique (max 20 derniers messages pour économiser les tokens)
        foreach (array_slice($history, -20) as $h) {
            $contents[] = [
                'role' => $h['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $h['content']]],
            ];
        }

        // Ajouter le nouveau message utilisateur
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $newMessage]],
        ];

        $body = json_encode([
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.9,
                'maxOutputTokens' => 1024,
                'topP' => 0.95,
            ],
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? $this->getFallbackResponse($newMessage);
        }

        return $this->getFallbackResponse($newMessage);
    }

    // ════════════════════════════════════════════════════════
    // PARSER LA RÉPONSE IA
    // ════════════════════════════════════════════════════════

    private function parseAiResponse(string $raw): array
    {
        // Nettoyer les balises markdown
        $clean = trim($raw);
        $clean = preg_replace('/^```json\s*/i', '', $clean);
        $clean = preg_replace('/^```\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/i', '', $clean);

        // Extraire le JSON
        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');
        if ($start !== false && $end !== false) {
            $json = substr($clean, $start, $end - $start + 1);
            $data = json_decode($json, true);
            if ($data && isset($data['text'])) {
                return $data;
            }
        }

        // Fallback : traiter comme texte brut
        return [
            'text' => $clean ?: "Je suis là pour toi ! Dis-moi comment tu vas aujourd'hui 😊",
            'type' => 'text',
            'quick_replies' => [],
            'mood_score' => null,
            'data_extracted' => [],
            'insight' => null,
            'xp_reward' => 5,
        ];
    }

    // ════════════════════════════════════════════════════════
    // MISE À JOUR DU PROFIL
    // ════════════════════════════════════════════════════════

    private function updateProfile(AiConversation $conv, AiUserProfile $profile, array $data): void
    {
        if (!empty($data['wake_up_time']))
            $profile->setWakeUpTime($data['wake_up_time']);
        if (!empty($data['sleep_time']))
            $profile->setSleepTime($data['sleep_time']);
        if (!empty($data['work_schedule']))
            $profile->setWorkSchedule($data['work_schedule']);
        if (!empty($data['exercise_frequency']))
            $profile->setExerciseFrequency($data['exercise_frequency']);

        if (!empty($data['stress_trigger'])) {
            $triggers = $profile->getStressTriggers();
            $triggers[] = $data['stress_trigger'];
            $profile->setStressTriggers(array_unique($triggers));
        }
        if (!empty($data['goal'])) {
            $goals = $conv->getGoals();
            $goals[] = $data['goal'];
            $conv->setGoals(array_unique($goals));
        }
        if (!empty($data['blocker'])) {
            $blockers = $conv->getBlockers();
            $blockers[] = $data['blocker'];
            $conv->setBlockers(array_unique($blockers));
        }
        if (!empty($data['strength'])) {
            $strengths = $conv->getStrengths();
            $strengths[] = $data['strength'];
            $conv->setStrengths(array_unique($strengths));
        }
        if (!empty($data['motivation_driver'])) {
            $drivers = $profile->getMotivationDrivers();
            $drivers[] = $data['motivation_driver'];
            $profile->setMotivationDrivers(array_unique($drivers));
        }
        if (!empty($data['achievement'])) {
            $achievements = $profile->getAchievements();
            $achievements[] = $data['achievement'];
            $profile->setAchievements($achievements);
        }
        if (!empty($data['value'])) {
            $values = $profile->getValues();
            $values[] = $data['value'];
            $profile->setValues(array_unique($values));
        }
        if (!empty($data['habit'])) {
            $habits = $conv->getHabits();
            $habits[] = $data['habit'];
            $conv->setHabits(array_unique($habits));
        }
    }

    // ════════════════════════════════════════════════════════
    // GAMIFICATION
    // ════════════════════════════════════════════════════════

    private function applyGamification(AiUserProfile $profile, int $xp): void
    {
        $profile->addXp($xp);

        // Streak
        $lastCheckin = $profile->getLastCheckinAt();
        $today = new \DateTimeImmutable('today');
        if (!$lastCheckin || $lastCheckin < $today) {
            $yesterday = $today->modify('-1 day');
            if ($lastCheckin && $lastCheckin >= $yesterday) {
                $profile->incrementStreak();
            } else {
                $profile->setStreakDays(1);
            }
            $profile->setLastCheckinAt(new \DateTimeImmutable());
        }

        // Badges automatiques
        if ($profile->getStreakDays() >= 3 && !in_array('🔥 Streak 3j', $profile->getBadges()))
            $profile->addBadge('🔥 Streak 3j');
        if ($profile->getStreakDays() >= 7 && !in_array('⚡ Streak 7j', $profile->getBadges()))
            $profile->addBadge('⚡ Streak 7j');
        if ($profile->getStreakDays() >= 30 && !in_array('🏆 Streak 30j', $profile->getBadges()))
            $profile->addBadge('🏆 Streak 30j');
        if ($profile->getTotalXp() >= 100 && !in_array('💎 100 XP', $profile->getBadges()))
            $profile->addBadge('💎 100 XP');
        if ($profile->getTotalXp() >= 500 && !in_array('🌟 500 XP', $profile->getBadges()))
            $profile->addBadge('🌟 500 XP');
    }

    // ════════════════════════════════════════════════════════
    // CRÉATION D'INSIGHT
    // ════════════════════════════════════════════════════════

    private function createInsight(User $user, array $insightData): void
    {
        $insight = new AiInsight();
        $insight->setUser($user);
        $insight->setType($insightData['type'] ?? AiInsight::TYPE_RECOMMENDATION);
        $insight->setTitle($insightData['title'] ?? 'Insight IA');
        $insight->setContent($insightData['content'] ?? '');
        $insight->setActionSuggested($insightData['action'] ?? null);
        $insight->setConfidence(0.85);
        $this->em->persist($insight);
    }

    // ════════════════════════════════════════════════════════
    // MESSAGE D'ACCUEIL INITIAL
    // ════════════════════════════════════════════════════════

    public function getWelcomeMessage(User $user, AiConversation $conv, AiUserProfile $profile): AiMessage
    {
        $prenom = $user->getPrenom() ?: 'toi';
        $hour = (int) date('H');
        $greeting = match (true) {
            $hour < 12 => "Bonjour",
            $hour < 18 => "Bon après-midi",
            default => "Bonsoir",
        };

        if (!$profile->isOnboardingComplete()) {
            $text = "{$greeting} {$prenom} ! 👋 Je suis **ARIA**, ton coach IA personnel sur Atomic You.\n\nJe suis là pour te connaître vraiment — pas juste en surface — et t'aider à devenir la meilleure version de toi-même, à ton rythme.\n\nPremière question : comment tu te sens là, maintenant, sur une échelle de 1 à 10 ?";
            $quickReplies = ['😴 1-3 (épuisé)', '😐 4-6 (moyen)', '😊 7-8 (bien)', '🔥 9-10 (top forme)'];
            $type = AiMessage::TYPE_MOOD_CHECK;
        } else {
            $streak = $profile->getStreakDays();
            $text = match (true) {
                $streak >= 7 => "{$greeting} {$prenom} ! 🔥 {$streak} jours consécutifs — tu es une machine ! Comment tu vas aujourd'hui ?",
                $streak >= 3 => "{$greeting} {$prenom} ! ⚡ Jour {$streak} de streak, impressionnant ! Prêt pour aujourd'hui ?",
                default => "{$greeting} {$prenom} ! Ravi de te retrouver. Comment s'est passée ta journée ?",
            };
            $quickReplies = ['Super bien 🚀', 'Moyen moyen 😐', 'Difficile 😓', 'Je veux parler 💬'];
            $type = AiMessage::TYPE_QUESTION;
        }

        $msg = new AiMessage();
        $msg->setConversation($conv);
        $msg->setRole(AiMessage::ROLE_ASSISTANT);
        $msg->setContent($text);
        $msg->setMessageType($type);
        $msg->setQuickReplies($quickReplies);
        $this->em->persist($msg);
        $this->em->flush();

        return $msg;
    }

    // ════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════

    private function buildHistory(AiConversation $conv): array
    {
        $history = [];
        foreach ($conv->getMessages() as $msg) {
            $history[] = [
                'role' => $msg->getRole(),
                'content' => $msg->getContent(),
            ];
        }
        return $history;
    }

    private function summarizeKnownData(AiConversation $conv, AiUserProfile $profile): string
    {
        $lines = [];
        if ($profile->getWakeUpTime())
            $lines[] = "- Réveil : {$profile->getWakeUpTime()}";
        if ($profile->getSleepTime())
            $lines[] = "- Coucher : {$profile->getSleepTime()}";
        if ($profile->getExerciseFrequency())
            $lines[] = "- Sport : {$profile->getExerciseFrequency()}";
        if ($profile->getWorkSchedule())
            $lines[] = "- Travail : {$profile->getWorkSchedule()}";

        $goals = $conv->getGoals();
        $blockers = $conv->getBlockers();
        $habits = $conv->getHabits();
        $drivers = $profile->getMotivationDrivers();
        $strengths = $conv->getStrengths();

        if (!empty($goals))
            $lines[] = "- Objectifs : " . implode(', ', array_slice($goals, 0, 3));
        if (!empty($blockers))
            $lines[] = "- Blocages : " . implode(', ', array_slice($blockers, 0, 3));
        if (!empty($habits))
            $lines[] = "- Habitudes : " . implode(', ', array_slice($habits, 0, 3));
        if (!empty($drivers))
            $lines[] = "- Motivateurs : " . implode(', ', array_slice($drivers, 0, 3));
        if (!empty($strengths))
            $lines[] = "- Forces : " . implode(', ', array_slice($strengths, 0, 3));

        return empty($lines) ? "Aucune donnée collectée pour l'instant." : implode("\n", $lines);
    }

    private function detectEmotion(string $text): string
    {
        $text = mb_strtolower($text);
        return match (true) {
            str_contains($text, 'stress') || str_contains($text, 'anxieux') || str_contains($text, 'peur') => 'stress',
            str_contains($text, 'triste') || str_contains($text, 'déprimé') || str_contains($text, 'mal') => 'sadness',
            str_contains($text, 'colère') || str_contains($text, 'énervé') || str_contains($text, 'frustré') => 'anger',
            str_contains($text, 'heureux') || str_contains($text, 'bien') || str_contains($text, 'super') => 'joy',
            str_contains($text, 'fatigué') || str_contains($text, 'épuisé') || str_contains($text, 'dormi') => 'fatigue',
            str_contains($text, 'motivé') || str_contains($text, 'envie') || str_contains($text, 'prêt') => 'motivation',
            default => 'neutral',
        };
    }

    private function extractDataFromMessage(string $text, AiConversation $conv): array
    {
        $data = [];
        // Heure de réveil
        if (preg_match('/\bje (me )?lève (à|vers) (\d{1,2}h?\d{0,2})/ui', $text, $m)) {
            $data['wake_up_time'] = $m[3];
        }
        // Heure de coucher
        if (preg_match('/\bje (me couche|dors) (à|vers) (\d{1,2}h?\d{0,2})/ui', $text, $m)) {
            $data['sleep_time'] = $m[3];
        }
        // Sport
        if (preg_match('/\b(\d+) fois (par semaine|par jour)/ui', $text, $m)) {
            $data['exercise_frequency'] = $m[0];
        }
        return array_filter($data);
    }

    private function getStyleGuide(string $style): string
    {
        return match ($style) {
            'direct' => "Sois direct, factuel, sans fioritures. L'utilisateur aime les conseils concrets et actionnables immédiatement.",
            'gentle' => "Sois doux, chaleureux, rassurant. Valide les émotions avant de donner des conseils. Utilise un langage bienveillant.",
            'humorous' => "Intègre de l'humour bienveillant, des métaphores fun, des références pop culture si pertinentes. Garde l'énergie légère et positive.",
            'structured' => "Sois structuré : listes, étapes, frameworks. L'utilisateur aime la clarté et l'organisation. Propose des plans en étapes.",
            default => "Adapte-toi naturellement au ton de l'utilisateur.",
        };
    }

    private function getFallbackResponse(string $userText): string
    {
        $responses = [
            '{"text": "Je t\'entends ! Dis-moi, qu\'est-ce qui t\'a le plus marqué aujourd\'hui ?", "type": "question", "quick_replies": ["Une victoire 🏆", "Un défi 💪", "Rien de spécial 😴"], "mood_score": null, "data_extracted": {}, "insight": null, "xp_reward": 5}',
            '{"text": "Intéressant ce que tu partages... Comment ça t\'affecte concrètement dans ta journée ?", "type": "question", "quick_replies": [], "mood_score": null, "data_extracted": {}, "insight": null, "xp_reward": 5}',
            '{"text": "Je suis là 100% avec toi. Continue, je t\'écoute.", "type": "text", "quick_replies": ["Voilà mon défi ⚡", "J\'ai une question 🤔", "Je veux de la motivation 🔥"], "mood_score": null, "data_extracted": {}, "insight": null, "xp_reward": 5}',
        ];
        return $responses[array_rand($responses)];
    }

    // ════════════════════════════════════════════════════════
    // TRIGGER MANUEL DE MOTIVATION (appelable depuis ailleurs)
    // ════════════════════════════════════════════════════════

    public function triggerMotivationBlast(User $user): AiMessage
    {
        $conv = $this->convRepo->findActiveForUser($user) ?? new AiConversation();
        $profile = $this->profileRepo->findOrCreateForUser($user, $this->em);
        $conv->setPhase(AiConversation::PHASE_MOTIVATION);
        return $this->chat($user, "J'ai besoin d'une boost de motivation maintenant !");
    }
}
<?php

namespace App\Service;

use App\Entity\DailyCheckin;
use App\Entity\User;

/**
 * Rule-based analytics from check-in history (no external AI).
 * Provides trend detection, sentiment extraction and categorised suggestions.
 */
class DailyCheckinAnalyticsService
{
    public function __construct(
        private readonly GroqService $groq
    ) {
    }

    /**
     * Get AI feedback for a specific check-in's text fields.
     */
    public function getAIFeedbackForJournal(DailyCheckin $c): array
    {
        $fields = [
            'Bien-être (Ce qui s\'est bien passé)' => $c->getWhatWentWell(),
            'Amélioration (Ce qui pourrait s\'améliorer)' => $c->getWhatCouldImprove(),
            'Gratitude' => $c->getGratitude(),
            'Défis' => $c->getMainChallenges(),
            'Victoires' => $c->getBiggestWins(),
            'Notes additionnelles' => $c->getAdditionalNotes(),
        ];

        // Only analyze if at least one field is filled
        $hasContent = false;
        $promptText = "Réflexions personnelles d'un journal quotidien. Propose un court message d'encouragement ou une piste de réflexion positive (1 à 2 phrases max) pour chaque point. Réponds uniquement en format JSON avec ces clés : 'bien_etre', 'amelioration', 'gratitude', 'defis', 'victoires', 'notes'.\n\n";
        
        foreach ($fields as $label => $val) {
            if ($val && trim($val) !== '') {
                $hasContent = true;
                $promptText .= "$label : $val\n";
            }
        }

        if (!$hasContent) {
            return [];
        }

        $aiResponse = $this->groq->generateResponse($promptText);
        
        // Robust JSON extraction
        $feedback = [];
        $isJson = false;
        if (preg_match('/\{.*\}/s', $aiResponse, $matches)) {
            $feedback = json_decode($matches[0], true);
            $isJson = true;
        }

        // Define categories for consistent return
        $categories = [
            'bien_etre' => ['label' => 'Qu\'est-ce qui s\'est bien passé ?', 'field' => 'getWhatWentWell', 'icon' => '🌟', 'color' => '#22c55e'],
            'amelioration' => ['label' => 'Ce qui pourrait s\'améliorer', 'field' => 'getWhatCouldImprove', 'icon' => '📈', 'color' => '#3b82f6'],
            'gratitude' => ['label' => 'Gratitude', 'field' => 'getGratitude', 'icon' => '🙏', 'color' => '#a855f7'],
            'defis' => ['label' => 'Principaux défis', 'field' => 'getMainChallenges', 'icon' => '🧩', 'color' => '#f43f5e'],
            'victoires' => ['label' => 'Plus grandes victoires', 'field' => 'getBiggestWins', 'icon' => '🏆', 'color' => '#eab308'],
            'notes' => ['label' => 'Notes additionnelles', 'field' => 'getAdditionalNotes', 'icon' => '📝', 'color' => '#94a3b8'],
        ];

        $results = [];
        foreach ($categories as $key => $info) {
            $method = $info['field'];
            
            $itemFeedback = $feedback[$key] ?? null;
            if (!$isJson && $aiResponse !== '') {
                $itemFeedback = $aiResponse;
            }
            if (!$itemFeedback) {
                $itemFeedback = 'Continuez à explorer cette dimension.';
            }

            $results[] = [
                'label' => $info['label'],
                'user_text' => $c->$method() ?: 'Non renseigné',
                'feedback' => $itemFeedback,
                'icon' => $info['icon'],
                'color' => $info['color']
            ];
        }

        return $results;
    }
    // ────────────────────────────────────────────────────────────────────────
    //  MAIN ANALYSIS (30 days)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @param DailyCheckin[] $checkins last 30 days, newest first
     * @return array<string, mixed>
     */
    public function buildThirtyDayAnalysis(User $user, array $checkins): array
    {
        $n = count($checkins);
        if ($n === 0) {
            return [
                'user'            => $user,
                'count'           => 0,
                'averages'        => null,
                'trends'          => [],
                'correlations'    => [],
                'patterns'        => [],
                'sentiment'       => [],
                'recommendations' => ['Ajoutez des check-ins réguliers pour voir des tendances sur 30 jours.'],
                'summary_report'  => '',
            ];
        }

        // ── Averages ──────────────────────────────────────────────────────
        $sum = ['mood' => 0.0, 'energy' => 0.0, 'productivity' => 0.0,
                'stress' => 0.0, 'sleepQuality' => 0.0, 'sleepHours' => 0.0];
        $gratitudeFilled = $winsFilled = $improveFilled = 0;

        foreach ($checkins as $c) {
            $sum['mood']         += $c->getMoodRating()        ?? 0;
            $sum['energy']       += $c->getEnergyLevel()       ?? 0;
            $sum['productivity'] += $c->getProductivityLevel() ?? 0;
            $sum['stress']       += $c->getStressLevel()       ?? 0;
            $sum['sleepQuality'] += $c->getSleepQuality()      ?? 0;
            $sum['sleepHours']   += $c->getSleepHours()        ?? 0.0;
            if ($c->getGratitude()      && trim($c->getGratitude())      !== '') { ++$gratitudeFilled; }
            if ($c->getBiggestWins()    && trim($c->getBiggestWins())    !== '') { ++$winsFilled; }
            if ($c->getWhatCouldImprove() && trim($c->getWhatCouldImprove()) !== '') { ++$improveFilled; }
        }

        $averages = [
            'mood'         => round($sum['mood'] / $n, 2),
            'energy'       => round($sum['energy'] / $n, 2),
            'productivity' => round($sum['productivity'] / $n, 2),
            'stress'       => round($sum['stress'] / $n, 2),
            'sleepQuality' => round($sum['sleepQuality'] / $n, 2),
            'sleepHours'   => round($sum['sleepHours'] / $n, 2),
        ];

        // ── 7-day vs 7-day trends ─────────────────────────────────────────
        $trends = $this->buildTrends($checkins);

        // ── Pearson correlations ──────────────────────────────────────────
        $moods    = array_map(fn($c) => (float) ($c->getMoodRating()        ?? 0), $checkins);
        $sleeps   = array_map(fn($c) => (float) ($c->getSleepHours()        ?? 0), $checkins);
        $stresses = array_map(fn($c) => (float) ($c->getStressLevel()       ?? 0), $checkins);
        $energies = array_map(fn($c) => (float) ($c->getEnergyLevel()       ?? 0), $checkins);

        $corrSleepMood    = $this->pearson($sleeps, $moods);
        $corrStressEnergy = $this->pearson($stresses, $energies);

        // ── Sentiment from text fields ────────────────────────────────────
        $allText = '';
        foreach ($checkins as $c) {
            $allText .= ' ' . ($c->getGratitude() ?? '');
            $allText .= ' ' . ($c->getBiggestWins() ?? '');
            $allText .= ' ' . ($c->getWhatWentWell() ?? '');
            $allText .= ' ' . ($c->getWhatCouldImprove() ?? '');
            $allText .= ' ' . ($c->getMainChallenges() ?? '');
            $allText .= ' ' . ($c->getAdditionalNotes() ?? '');
        }
        $sentiment = $this->analyzeSentiment($allText);

        // ── Patterns ──────────────────────────────────────────────────────
        $patterns = [];
        $patterns[] = sprintf(
            'Sur %d jours : gratitude renseignée dans %d entrées, victoires dans %d, axes d\'amélioration dans %d.',
            $n, $gratitudeFilled, $winsFilled, $improveFilled
        );
        if ($averages['sleepHours'] >= 7.0 && $corrSleepMood !== null && $corrSleepMood > 0.2) {
            $patterns[] = 'Plus vous dormez, meilleure est votre humeur (corrélation positive détectée).';
        }
        if ($averages['stress'] > 6.5 && $corrStressEnergy !== null && $corrStressEnergy < -0.2) {
            $patterns[] = 'Un stress élevé est associé à une baisse d\'énergie dans vos données.';
        }

        // ── Recommendations ───────────────────────────────────────────────
        $recommendations = $this->buildRecommendations($averages, $gratitudeFilled, $n, $corrSleepMood, $corrStressEnergy);

        // ── Narrative summary ─────────────────────────────────────────────
        $report = $this->buildNarrativeReport($averages, $trends, $sentiment, $n);

        return [
            'user'            => $user,
            'count'           => $n,
            'averages'        => $averages,
            'trends'          => $trends,
            'correlations'    => [
                'sleep_hours_vs_mood' => $corrSleepMood,
                'stress_vs_energy'    => $corrStressEnergy,
            ],
            'patterns'        => $patterns,
            'sentiment'       => $sentiment,
            'recommendations' => $recommendations,
            'summary_report'  => $report,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    //  SUGGESTIONS (categorised)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @param DailyCheckin[] $checkins
     * @return array<string, mixed>  keyed by category
     */
    public function buildSuggestions(array $checkins): array
    {
        if (count($checkins) === 0) {
            return [
                'categories' => [
                    [
                        'icon'  => '📋',
                        'label' => 'Démarrage',
                        'color' => '#6c63ff',
                        'items' => ['Commencez par un premier check-in pour recevoir des conseils adaptés à vos données.'],
                    ],
                ],
                'averages' => null,
                'challenge' => null,
            ];
        }

        $n           = count($checkins);
        $avgMood     = 0.0; $avgStress = 0.0;
        $avgSleepQ   = 0.0; $avgSleepH = 0.0;
        $avgEnergy   = 0.0; $avgProd   = 0.0;
        $gratEmpty   = 0;   $winsEmpty = 0;

        foreach ($checkins as $c) {
            $avgMood   += $c->getMoodRating()        ?? 0;
            $avgStress += $c->getStressLevel()       ?? 0;
            $avgSleepQ += $c->getSleepQuality()      ?? 0;
            $avgSleepH += $c->getSleepHours()        ?? 0;
            $avgEnergy += $c->getEnergyLevel()       ?? 0;
            $avgProd   += $c->getProductivityLevel() ?? 0;
            if (!$c->getGratitude()   || trim($c->getGratitude())   === '') { ++$gratEmpty; }
            if (!$c->getBiggestWins() || trim($c->getBiggestWins()) === '') { ++$winsEmpty; }
        }
        $avgMood   /= $n; $avgStress /= $n;
        $avgSleepQ /= $n; $avgSleepH /= $n;
        $avgEnergy /= $n; $avgProd   /= $n;

        $avgs = compact('avgMood', 'avgStress', 'avgSleepQ', 'avgSleepH', 'avgEnergy', 'avgProd');

        $categories = [];

        // ── Mood category ─────────────────────────────────────────────────
        $moodItems = [];
        if ($avgMood < 5.0) {
            $moodItems[] = '🎵 Écoutez 10 minutes de musique qui vous fait du bien dès le matin.';
            $moodItems[] = '🚶 Prévoyez une courte marche en plein air (même 5 minutes).';
            $moodItems[] = '📞 Envoyez un message positif à un proche aujourd\'hui.';
            $moodItems[] = '🧘 Essayez une respiration en boîte : 4s inspir, 4s pause, 4s expir, 4s pause.';
        } elseif ($avgMood <= 7.0) {
            $moodItems[] = '🎯 Définissez un petit objectif atteignable pour aujourd\'hui.';
            $moodItems[] = '💪 Intégrez 15 minutes d\'activité légère dans votre journée.';
            $moodItems[] = '✍️ Identifiez une chose positive qui s\'est passée cette semaine.';
        } else {
            $moodItems[] = '🎉 Votre humeur est au beau fixe — célébrez vos efforts !';
            $moodItems[] = '🌱 Utilisez cette énergie positive pour créer une nouvelle habitude.';
        }
        $categories[] = [
            'icon'  => '😊',
            'label' => 'Humeur & Moral',
            'color' => $avgMood < 5 ? '#f43f5e' : ($avgMood <= 7 ? '#f59e0b' : '#22c55e'),
            'items' => $moodItems,
        ];

        // ── Stress category ───────────────────────────────────────────────
        $stressItems = [];
        if ($avgStress > 7.0) {
            $stressItems[] = '🌬️ Pratiquez la respiration 4-7-8 trois fois par jour (4s inspir, 7s pause, 8s expir).';
            $stressItems[] = '📵 Programmez une "détox numérique" d\'1 heure le soir.';
            $stressItems[] = '🛁 Ajoutez un rituel de relaxation : bain chaud, tisane, lecture légère.';
            $stressItems[] = '🗒️ Décomposez vos tâches en micro-étapes pour réduire la pression mentale.';
        } elseif ($avgStress >= 5.0) {
            $stressItems[] = '⏰ Utilisez le time-blocking : des blocs de 45 min de travail, 10 min de pause.';
            $stressItems[] = '📋 Limitez votre liste de tâches à 3 priorités max par jour.';
            $stressItems[] = '🧩 Prévoyez des pauses structurées (méthode Pomodoro).';
        } else {
            $stressItems[] = '✨ Votre gestion du stress est excellente — maintenez vos routines anti-stress !';
        }
        $categories[] = [
            'icon'  => '🧠',
            'label' => 'Gestion du Stress',
            'color' => $avgStress > 7 ? '#f43f5e' : ($avgStress >= 5 ? '#f59e0b' : '#22c55e'),
            'items' => $stressItems,
        ];

        // ── Sleep category ────────────────────────────────────────────────
        $sleepItems = [];
        if ($avgSleepH < 6.0 || $avgSleepQ < 5.0) {
            $sleepItems[] = '🌙 Adoptez des horaires de coucher et de lever fixes, même le week-end.';
            $sleepItems[] = '📵 Éteignez les écrans au moins 45 minutes avant de dormir.';
            $sleepItems[] = '🌡️ Gardez votre chambre fraîche (18-20°C) et obscure.';
            $sleepItems[] = '☕ Évitez caféine et alcool après 15h.';
        } elseif ($avgSleepH < 7.0) {
            $sleepItems[] = '⏱️ Essayez de gagner 30 minutes de sommeil en vous couchant plus tôt.';
            $sleepItems[] = '📖 Remplacez les réseaux sociaux au lit par 10 min de lecture.';
        } else {
            $sleepItems[] = '😴 Votre sommeil est optimal — continuez ces bonnes habitudes !';
        }
        $categories[] = [
            'icon'  => '💤',
            'label' => 'Qualité du Sommeil',
            'color' => ($avgSleepH < 6 || $avgSleepQ < 5) ? '#f43f5e' : ($avgSleepH < 7 ? '#f59e0b' : '#22c55e'),
            'items' => $sleepItems,
        ];

        // ── Journaling habits ─────────────────────────────────────────────
        $journalItems = [];
        if ($gratEmpty > $n / 2) {
            $journalItems[] = '🙏 Écrivez chaque soir 3 choses simples pour lesquelles vous êtes reconnaissant(e).';
            $journalItems[] = '📝 Commencez petit : une phrase suffit. L\'habitude prime sur la quantité.';
        }
        if ($winsEmpty > $n / 2) {
            $journalItems[] = '🏆 Documentez au moins une "victoire" par jour, aussi petite soit-elle.';
        }
        if (empty($journalItems)) {
            $journalItems[] = '✅ Vous êtes assidu(e) dans votre journal — excellent travail !';
            $journalItems[] = '💡 Pour aller plus loin, analysez les thèmes récurrents dans vos écrits.';
        }
        $categories[] = [
            'icon'  => '📔',
            'label' => 'Journaling & Gratitude',
            'color' => $gratEmpty > $n / 2 ? '#f59e0b' : '#22c55e',
            'items' => $journalItems,
        ];

        // ── Energy & Productivity ─────────────────────────────────────────
        $perfItems = [];
        if ($avgEnergy < 5.0) {
            $perfItems[] = '🏃 Un exercice court (10-15 min) en matinée booste l\'énergie pour toute la journée.';
            $perfItems[] = '💧 Hydratez-vous dès le réveil : un grand verre d\'eau avant le café.';
        }
        if ($avgProd < 5.0) {
            $perfItems[] = '📌 Identifiez votre "pic de productivité" dans la journée et planifiez vos tâches importantes à ce moment.';
            $perfItems[] = '🚫 Éliminez les distractions : mode silence, applications bloquées pendant 1h.';
        }
        if (empty($perfItems)) {
            $perfItems[] = '⚡ Énergie et productivité au top — capitalisez sur cette dynamique !';
        }
        $categories[] = [
            'icon'  => '⚡',
            'label' => 'Énergie & Productivité',
            'color' => ($avgEnergy < 5 || $avgProd < 5) ? '#f59e0b' : '#22c55e',
            'items' => $perfItems,
        ];

        // ── Daily challenge ───────────────────────────────────────────────
        $challenges = [
            'Sans écran pendant la première heure du matin.',
            'Boire un grand verre d\'eau avant le café.',
            '10 minutes de marche après le déjeuner.',
            'Écrire 2 lignes sur ce qui s\'est bien passé.',
            '5 minutes de respiration guidée avant de dormir.',
            'Appeler un proche pour un échange court et positif.',
            'Ranger un petit coin de votre espace de travail.',
            'Lister 3 priorités pour demain — seulement 3.',
            'Faire une pause de 5 minutes toutes les heures de travail.',
            'Sourire à 3 personnes différentes aujourd\'hui.',
        ];
        $challenge = $challenges[array_rand($challenges)];

        return [
            'categories' => $categories,
            'averages'   => $avgs,
            'challenge'  => $challenge,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    //  INTERNAL: TREND DETECTION (7-day vs previous 7-day)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @param DailyCheckin[] $checkins newest first
     * @return array<string, mixed>
     */
    private function buildTrends(array $checkins): array
    {
        // Split into last-7 and previous-7
        $last7 = array_slice($checkins, 0, 7);
        $prev7 = array_slice($checkins, 7, 7);

        if (count($last7) === 0 || count($prev7) === 0) {
            return [];
        }

        $metrics = ['mood' => 'getMoodRating', 'energy' => 'getEnergyLevel',
                    'stress' => 'getStressLevel', 'productivity' => 'getProductivityLevel',
                    'sleepHours' => 'getSleepHours'];
        $labels  = ['mood' => 'Humeur', 'energy' => 'Énergie', 'stress' => 'Stress',
                    'productivity' => 'Productivité', 'sleepHours' => 'Heures de sommeil'];
        $trends  = [];

        foreach ($metrics as $key => $method) {
            $avgLast = $this->avg(array_map(fn($c) => (float) ($c->$method() ?? 0), $last7));
            $avgPrev = $this->avg(array_map(fn($c) => (float) ($c->$method() ?? 0), $prev7));
            $diff    = $avgLast - $avgPrev;
            $pct     = $avgPrev > 0 ? round(($diff / $avgPrev) * 100, 1) : 0.0;

            // For stress, positive diff = worsening
            $improving = $key === 'stress' ? $diff < -0.3 : $diff > 0.3;
            $declining = $key === 'stress' ? $diff > 0.3  : $diff < -0.3;

            $trends[$key] = [
                'label'     => $labels[$key],
                'last7avg'  => round($avgLast, 2),
                'prev7avg'  => round($avgPrev, 2),
                'diff'      => round($diff, 2),
                'pct'       => $pct,
                'direction' => $improving ? 'up' : ($declining ? 'down' : 'stable'),
                'icon'      => $improving ? '↑' : ($declining ? '↓' : '→'),
                'color'     => $improving ? '#22c55e' : ($declining ? '#f43f5e' : '#f59e0b'),
            ];
        }

        return $trends;
    }

    // ────────────────────────────────────────────────────────────────────────
    //  INTERNAL: SENTIMENT ANALYSIS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function analyzeSentiment(string $text): array
    {
        $text = mb_strtolower($text);

        $lexicon = [
            'joy'       => ['heureux','heureuse','joie','super','génial','excellent','content','contente',
                            'bien','positif','bonne','bonheur','motivation','enthousias','fier','fière',
                            'réussi','accompli','confiant','soulagé','formidable','merveilleux','love'],
            'gratitude' => ['reconnaissant','reconnaissante','merci','gratitude','chance','chanceux',
                            'chanceux','béni','bénie','apprécier','apprécié','apprécie','grâce'],
            'stress'    => ['stressé','stressée','stress','anxieux','anxieuse','pression','overwhelm',
                            'submergé','débordé','angoisse','tendu','tendue','borné','inquiet','inquiète'],
            'sadness'   => ['triste','tristesse','déprimé','déprimée','cafard','morose','sombre',
                            'découragé','découragée','désespoir','seul','seule','ennui','difficile'],
            'anger'     => ['frustré','frustré','frustration','colère','énervé','énervée','agacé',
                            'agacée','irrité','irritée','insupportable','marre','déçu','déçue'],
            'fear'      => ['peur','crainte','craindre','effrayé','effrayée','anxieux','anxiété',
                            'incertitude','doute','douteux','risque','inconnu'],
        ];

        $counts = array_fill_keys(array_keys($lexicon), 0);
        $total  = 0;

        foreach ($lexicon as $emotion => $words) {
            foreach ($words as $word) {
                $occurrences = substr_count($text, $word);
                $counts[$emotion] += $occurrences;
                $total += $occurrences;
            }
        }

        if ($total === 0) {
            return [];
        }

        $labels = [
            'joy'       => ['label' => 'Joie',       'color' => '#22c55e', 'icon' => '😊'],
            'gratitude' => ['label' => 'Gratitude',  'color' => '#a855f7', 'icon' => '🙏'],
            'stress'    => ['label' => 'Stress',     'color' => '#f43f5e', 'icon' => '😰'],
            'sadness'   => ['label' => 'Tristesse',  'color' => '#3b82f6', 'icon' => '😔'],
            'anger'     => ['label' => 'Frustration','color' => '#f97316', 'icon' => '😤'],
            'fear'      => ['label' => 'Inquiétude', 'color' => '#eab308', 'icon' => '😟'],
        ];

        $result = [];
        arsort($counts);
        foreach ($counts as $emotion => $count) {
            if ($count === 0) { continue; }
            $result[] = array_merge($labels[$emotion], [
                'count' => $count,
                'pct'   => round($count / $total * 100, 1),
            ]);
        }

        return $result;
    }

    // ────────────────────────────────────────────────────────────────────────
    //  INTERNAL: NARRATIVE REPORT
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @param array<string,float>  $averages
     * @param array<string,mixed>  $trends
     * @param array<int,mixed>     $sentiment
     */
    private function buildNarrativeReport(array $averages, array $trends, array $sentiment, int $n): string
    {
        $lines = [];

        // Emotional state
        $moodEmoji = $averages['mood'] >= 7 ? '😄' : ($averages['mood'] >= 5 ? '😐' : '😔');
        $moodState = $averages['mood'] >= 7 ? 'bonne' : ($averages['mood'] >= 5 ? 'modérée' : 'basse');
        $lines[] = "## {$moodEmoji} État émotionnel\nVotre humeur moyenne sur 30 jours est de **{$averages['mood']}/10** — globalement **{$moodState}**.";

        // Energy
        $energyEmoji = $averages['energy'] >= 7 ? '⚡' : ($averages['energy'] >= 5 ? '🔋' : '😴');
        $lines[] = "## {$energyEmoji} Niveau d'énergie\nÉnergie moyenne : **{$averages['energy']}/10**.";

        // Stress
        $stressEmoji = $averages['stress'] <= 4 ? '😌' : ($averages['stress'] <= 6 ? '😤' : '🚨');
        $stressLabel = $averages['stress'] <= 4 ? 'faible' : ($averages['stress'] <= 6 ? 'modéré' : 'élevé');
        $lines[] = "## {$stressEmoji} Gestion du stress\nNiveau de stress moyen : **{$averages['stress']}/10** — niveau **{$stressLabel}**.";

        // Sleep
        $sleepEmoji = $averages['sleepHours'] >= 7 ? '😴' : ($averages['sleepHours'] >= 6 ? '🌙' : '⚠️');
        $sleepLabel = $averages['sleepHours'] >= 7 ? 'optimal' : ($averages['sleepHours'] >= 6 ? 'correct' : 'insuffisant');
        $lines[] = "## {$sleepEmoji} Qualité du sommeil\nMoyenne : **{$averages['sleepHours']}h** de sommeil — niveau **{$sleepLabel}**.";

        // Trends
        if (!empty($trends)) {
            $trendLines = [];
            foreach ($trends as $t) {
                $trendLines[] = "- {$t['icon']} **{$t['label']}** : {$t['last7avg']} (7 derniers jours) vs {$t['prev7avg']} (7 jours précédents)";
            }
            $lines[] = "## 📈 Tendances récentes\n" . implode("\n", $trendLines);
        }

        // Sentiment
        if (!empty($sentiment)) {
            $topEmotions = array_slice($sentiment, 0, 3);
            $emotionList = implode(', ', array_map(fn($e) => "{$e['icon']} {$e['label']} ({$e['pct']}%)", $topEmotions));
            $lines[] = "## 💬 Analyse émotionnelle du journal\nÉmotions dominantes détectées dans vos écrits : {$emotionList}.";
        }

        // Personalised note
        $lines[] = "## 🎯 Observations personnalisées\nBasé sur vos **{$n} check-ins**, continuez à observer comment le sommeil et le stress influencent votre énergie. Ajustez vos routines selon les tendances détectées.";

        return implode("\n\n", $lines);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  INTERNAL: CLASSIC RECOMMENDATIONS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @return array<int, string>
     */
    private function buildRecommendations(array $averages, int $gratitudeFilled, int $n, ?float $corrSleepMood, ?float $corrStressEnergy): array
    {
        $out = [];

        if ($averages['mood'] < 5.5) {
            $out[] = 'Renforcer le moral : planifiez une micro-récompense quotidienne et une sortie légère chaque semaine.';
        }
        if ($averages['stress'] > 6.5) {
            $out[] = 'Gestion du stress : découpez les tâches, fixez des limites horaires, et prévoyez des pauses courtes.';
        }
        if ($averages['sleepQuality'] < 6.0 || $averages['sleepHours'] < 6.5) {
            $out[] = 'Sommeil : visez une fenêtre de sommeil régulière et limitez café/alcool en fin de journée.';
        }
        if ($gratitudeFilled < max(1, (int) ceil($n * 0.4))) {
            $out[] = 'Gratitude : complétez le champ gratitude au moins 4 jours sur 7 pour ancrer le positif.';
        }
        if ($corrSleepMood !== null && $corrSleepMood > 0.25) {
            $out[] = 'Corrélation observée : plus de sommeil semble lié à une meilleure humeur — priorisez la durée de sommeil.';
        } elseif ($corrSleepMood !== null && $corrSleepMood < -0.25) {
            $out[] = 'Sommeil et humeur semblent peu alignés — notez aussi le stress et l\'activité pour affiner.';
        }
        if ($corrStressEnergy !== null && $corrStressEnergy < -0.3) {
            $out[] = 'Quand le stress monte, l\'énergie baisse souvent — anticipez les pics (planification, pauses).';
        }

        $fillers = [
            'Maintenez un check-in quotidien pour affiner les tendances.',
            'Variez légèrement votre routine pour repérer ce qui influence le mieux votre énergie.',
            'Notez un défi réussi chaque semaine pour renforcer la confiance.',
        ];
        $fi = 0;
        while (count($out) < 5 && $fi < count($fillers)) {
            $out[] = $fillers[$fi++];
        }

        return array_slice(array_unique($out), 0, 5);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  HELPERS
    // ────────────────────────────────────────────────────────────────────────

    /** @param float[] $arr */
    private function avg(array $arr): float
    {
        return count($arr) > 0 ? array_sum($arr) / count($arr) : 0.0;
    }

    /**
     * @param list<float> $x
     * @param list<float> $y
     */
    private function pearson(array $x, array $y): ?float
    {
        $n = min(count($x), count($y));
        if ($n < 3) { return null; }
        $x = array_slice($x, 0, $n);
        $y = array_slice($y, 0, $n);

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $num = $denX = $denY = 0.0;
        for ($i = 0; $i < $n; ++$i) {
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            $num  += $dx * $dy;
            $denX += $dx * $dx;
            $denY += $dy * $dy;
        }

        $den = sqrt($denX * $denY);
        return $den < 1e-9 ? null : round($num / $den, 3);
    }
}

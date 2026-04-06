<?php

namespace App\Service;

use App\Entity\DailyCheckin;
use App\Entity\User;

/**
 * Rule-based analytics from check-in history (no AI).
 */
class DailyCheckinAnalyticsService
{
    /**
     * @param DailyCheckin[] $checkins last 30 days, newest first optional
     * @return array<string, mixed>
     */
    public function buildThirtyDayAnalysis(User $user, array $checkins): array
    {
        $n = count($checkins);
        if ($n === 0) {
            return [
                'count' => 0,
                'averages' => null,
                'correlations' => [],
                'patterns' => [],
                'recommendations' => ['Ajoutez des check-ins réguliers pour voir des tendances sur 30 jours.'],
            ];
        }

        $sum = [
            'mood' => 0.0,
            'energy' => 0.0,
            'productivity' => 0.0,
            'stress' => 0.0,
            'sleepQuality' => 0.0,
            'sleepHours' => 0.0,
        ];

        $gratitudeFilled = 0;
        $winsFilled = 0;
        $improveFilled = 0;

        foreach ($checkins as $c) {
            $sum['mood'] += $c->getMoodRating() ?? 0;
            $sum['energy'] += $c->getEnergyLevel() ?? 0;
            $sum['productivity'] += $c->getProductivityLevel() ?? 0;
            $sum['stress'] += $c->getStressLevel() ?? 0;
            $sum['sleepQuality'] += $c->getSleepQuality() ?? 0;
            $sum['sleepHours'] += $c->getSleepHours() ?? 0.0;
            if ($c->getGratitude() && trim($c->getGratitude()) !== '') {
                ++$gratitudeFilled;
            }
            if ($c->getBiggestWins() && trim($c->getBiggestWins()) !== '') {
                ++$winsFilled;
            }
            if ($c->getWhatCouldImprove() && trim($c->getWhatCouldImprove()) !== '') {
                ++$improveFilled;
            }
        }

        $averages = [
            'mood' => round($sum['mood'] / $n, 2),
            'energy' => round($sum['energy'] / $n, 2),
            'productivity' => round($sum['productivity'] / $n, 2),
            'stress' => round($sum['stress'] / $n, 2),
            'sleepQuality' => round($sum['sleepQuality'] / $n, 2),
            'sleepHours' => round($sum['sleepHours'] / $n, 2),
        ];

        $moods = [];
        $sleeps = [];
        $stresses = [];
        $energies = [];
        foreach ($checkins as $c) {
            $moods[] = (float) ($c->getMoodRating() ?? 0);
            $sleeps[] = (float) ($c->getSleepHours() ?? 0);
            $stresses[] = (float) ($c->getStressLevel() ?? 0);
            $energies[] = (float) ($c->getEnergyLevel() ?? 0);
        }

        $corrSleepMood = $this->pearson($sleeps, $moods);
        $corrStressEnergy = $this->pearson($stresses, $energies);

        $patterns = [];
        $patterns[] = sprintf(
            'Sur 30 jours : gratitude renseignée dans %d/%d entrées, victoires dans %d/%d, axes d\'amélioration dans %d/%d.',
            $gratitudeFilled,
            $n,
            $winsFilled,
            $n,
            $improveFilled,
            $n
        );

        $recommendations = $this->buildRecommendations($averages, $gratitudeFilled, $n, $corrSleepMood, $corrStressEnergy);

        return [
            'user' => $user,
            'count' => $n,
            'averages' => $averages,
            'correlations' => [
                'sleep_hours_vs_mood' => $corrSleepMood,
                'stress_vs_energy' => $corrStressEnergy,
            ],
            'patterns' => $patterns,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @param DailyCheckin[] $checkins
     * @return array<int, string>
     */
    public function buildSuggestions(array $checkins): array
    {
        if (count($checkins) === 0) {
            return ['Commencez par un premier check-in pour recevoir des conseils adaptés à vos données.'];
        }

        $n = count($checkins);
        $avgMood = 0.0;
        $avgStress = 0.0;
        $avgSleepQ = 0.0;
        $avgSleepH = 0.0;
        $gratitudeEmpty = 0;

        foreach ($checkins as $c) {
            $avgMood += $c->getMoodRating() ?? 0;
            $avgStress += $c->getStressLevel() ?? 0;
            $avgSleepQ += $c->getSleepQuality() ?? 0;
            $avgSleepH += $c->getSleepHours() ?? 0;
            if (!$c->getGratitude() || trim($c->getGratitude()) === '') {
                ++$gratitudeEmpty;
            }
        }

        $avgMood /= $n;
        $avgStress /= $n;
        $avgSleepQ /= $n;
        $avgSleepH /= $n;

        $tips = [];

        if ($avgMood < 5.0) {
            $tips[] = 'Humeur basse récente : prévoyez 10 minutes de marche ou d\'étirements, et une activité plaisir courte (musique, ami).';
        }
        if ($avgStress > 7.0) {
            $tips[] = 'Stress élevé : essayez la respiration 4-7-8 (4s inspiration, 7s rétention, 8s expiration) 3 fois par jour.';
        }
        if ($avgSleepQ < 5.0 || $avgSleepH < 6.0) {
            $tips[] = 'Sommeil : coucher et lever à heures fixes, pas d\'écran 45 min avant le sommeil, chambre fraîche et sombre.';
        }
        if ($gratitudeEmpty > $n / 2) {
            $tips[] = 'Peu de gratitude notée : chaque soir, écrivez 3 choses simples positives (même minuscules).';
        }

        $challenges = [
            'Sans écran pendant la première heure du matin.',
            'Boire un grand verre d\'eau avant le café.',
            '10 minutes de marche après le déjeuner.',
            'Écrire 2 lignes sur ce qui s\'est bien passé.',
            '5 minutes de respiration guidée avant de dormir.',
            'Appeler un proche pour un échange court et positif.',
            'Ranger un petit coin de votre espace de travail.',
            'Lister 3 priorités pour demain (pas plus).',
        ];
        $tips[] = 'Défi du jour : '.$challenges[random_int(0, count($challenges) - 1)];

        if ($tips === []) {
            $tips[] = 'Continuez : vos indicateurs sont équilibrés. Gardez le rythme des check-ins pour ajuster au fil des semaines.';
        }

        return $tips;
    }

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

    /**
     * @param list<float> $x
     * @param list<float> $y
     */
    private function pearson(array $x, array $y): ?float
    {
        $n = min(count($x), count($y));
        if ($n < 3) {
            return null;
        }
        $x = array_slice($x, 0, $n);
        $y = array_slice($y, 0, $n);

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $num = 0.0;
        $denX = 0.0;
        $denY = 0.0;
        for ($i = 0; $i < $n; ++$i) {
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            $num += $dx * $dy;
            $denX += $dx * $dx;
            $denY += $dy * $dy;
        }

        $den = sqrt($denX * $denY);
        if ($den < 1e-9) {
            return null;
        }

        return round($num / $den, 3);
    }
}

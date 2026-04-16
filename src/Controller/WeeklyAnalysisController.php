<?php

namespace App\Controller;

use App\Entity\MentalEntry;
use App\Repository\MentalEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class WeeklyAnalysisController extends AbstractController
{
    public function __construct(
        private readonly MentalEntryRepository $mentalEntryRepository,
    ) {
    }

    #[Route('/mental-analysis/weekly', name: 'app_weekly_analysis', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dashboard/weekly_analysis.html.twig', $this->buildWeeklyAnalysisContext());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWeeklyAnalysisContext(): array
    {
        $today = new \DateTimeImmutable('today');
        $currentWeekStart = $today->modify('monday this week');
        $currentWeekEnd = $currentWeekStart->modify('+6 days');
        $previousWeekStart = $currentWeekStart->modify('-7 days');
        $previousWeekEnd = $currentWeekStart->modify('-1 day');

        $entries = $this->mentalEntryRepository->findForDashboard();

        $currentWeekEntries = $this->filterEntriesByDateRange($entries, $currentWeekStart, $currentWeekEnd);
        $previousWeekEntries = $this->filterEntriesByDateRange($entries, $previousWeekStart, $previousWeekEnd);

        $currentWeekData = $this->buildWeekData($currentWeekEntries, $currentWeekStart);
        $previousWeekData = $this->buildWeekData($previousWeekEntries, $previousWeekStart);

        $currentAverage = $currentWeekData['average'];
        $previousAverage = $previousWeekData['average'];
        $delta = round($currentAverage - $previousAverage, 2);
        $mentalIndex = $this->computeMentalIndex($currentAverage, $delta);

        $mostStressfulEntry = $this->findMostStressfulEntry($currentWeekEntries);
        $mostStressfulDay = $mostStressfulEntry?->getEntryDate()?->format('l d/m');
        $mostStressfulDayFr = null !== $mostStressfulDay ? $this->translateEnglishDayToFrench($mostStressfulDay) : null;

        return [
            'active_section' => 'analysis',
            'current_week_range' => sprintf(
                '%s - %s',
                $currentWeekStart->format('d/m'),
                $currentWeekEnd->format('d/m')
            ),
            'current_average' => $currentAverage,
            'previous_average' => $previousAverage,
            'delta' => $delta,
            'mental_index' => $mentalIndex,
            'stressful_day' => $mostStressfulDayFr ?? 'Aucun',
            'stressful_level' => $mostStressfulEntry?->getEmotionLevel(),
            'chart_labels' => ['lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.', 'dim.'],
            'chart_current_values' => $currentWeekData['dailyAverages'],
            'chart_previous_values' => $previousWeekData['dailyAverages'],
            'insights' => $this->buildInsights($currentAverage, $delta, $mostStressfulEntry),
        ];
    }

    /**
     * @param MentalEntry[] $entries
     * @return MentalEntry[]
     */
    private function filterEntriesByDateRange(array $entries, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return array_values(array_filter($entries, static function (MentalEntry $entry) use ($start, $end): bool {
            $date = $entry->getEntryDate();
            if (null === $date) {
                return false;
            }

            return $date >= $start && $date <= $end;
        }));
    }

    /**
     * @param MentalEntry[] $entries
     * @return array{average: float, dailyAverages: array<int, float>}
     */
    private function buildWeekData(array $entries, \DateTimeImmutable $weekStart): array
    {
        $dayBuckets = array_fill(0, 7, []);

        foreach ($entries as $entry) {
            $entryDate = $entry->getEntryDate();
            if (null === $entryDate) {
                continue;
            }

            $dayOffset = (int) $weekStart->diff($entryDate)->days;
            if ($dayOffset < 0 || $dayOffset > 6 || null === $entry->getEmotionLevel()) {
                continue;
            }

            $dayBuckets[$dayOffset][] = $entry->getEmotionLevel();
        }

        $dailyAverages = [];
        $allLevels = [];

        foreach ($dayBuckets as $levels) {
            if ([] === $levels) {
                $dailyAverages[] = 0.0;
                continue;
            }

            $dailyAverage = round(array_sum($levels) / count($levels), 2);
            $dailyAverages[] = $dailyAverage;
            array_push($allLevels, ...$levels);
        }

        $average = [] === $allLevels ? 0.0 : round(array_sum($allLevels) / count($allLevels), 2);

        return [
            'average' => $average,
            'dailyAverages' => $dailyAverages,
        ];
    }

    /**
     * @param MentalEntry[] $entries
     */
    private function findMostStressfulEntry(array $entries): ?MentalEntry
    {
        usort($entries, static function (MentalEntry $a, MentalEntry $b): int {
            return ($b->getEmotionLevel() ?? 0) <=> ($a->getEmotionLevel() ?? 0);
        });

        return $entries[0] ?? null;
    }

    private function computeMentalIndex(float $currentAverage, float $delta): float
    {
        $base = 10 - $currentAverage;
        $stabilityBonus = $delta <= 0 ? min(abs($delta), 2.5) : -min($delta, 2.5);
        $score = $base + $stabilityBonus;

        return round(max(0, min(10, $score)), 2);
    }

    /**
     * @return string[]
     */
    private function buildInsights(float $currentAverage, float $delta, ?MentalEntry $stressfulEntry): array
    {
        $insights = [];

        if (0.0 === $currentAverage) {
            return ['Semaine sans données: ajoute quelques entries pour obtenir une analyse fiable.'];
        }

        if ($currentAverage >= 7.0) {
            $insights[] = 'Semaine intense: essaie une pause active (respiration, marche, hydratation).';
        } elseif ($currentAverage <= 4.0) {
            $insights[] = 'Semaine globalement stable: conserve les routines qui fonctionnent.';
        } else {
            $insights[] = 'Niveau émotionnel modéré: surveille les pics pour éviter l’accumulation.';
        }

        if ($delta >= 1.0) {
            $insights[] = sprintf('Hausse de %.2f vs semaine précédente: ajuste ton rythme en début de journée.', $delta);
        } elseif ($delta <= -1.0) {
            $insights[] = sprintf('Amélioration de %.2f vs semaine précédente: continue les bonnes habitudes.', abs($delta));
        } else {
            $insights[] = 'Évolution stable vs semaine précédente.';
        }

        if (null !== $stressfulEntry && null !== $stressfulEntry->getEmotionLevel()) {
            $day = $stressfulEntry->getEntryDate()?->format('l');
            $dayFr = $this->translateEnglishDayToFrench($day ?? 'jour inconnu');
            $insights[] = sprintf(
                'Jour le plus stressant: %s (niveau %d). Planifie une activité légère ce jour-là.',
                $dayFr,
                $stressfulEntry->getEmotionLevel()
            );
        }

        return $insights;
    }

    private function translateEnglishDayToFrench(string $value): string
    {
        $map = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche',
        ];

        return str_replace(array_keys($map), array_values($map), $value);
    }
}

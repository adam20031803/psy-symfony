<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\User;
use App\Repository\ReclamationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Challenge;
use App\Entity\MentalEntry;
use App\Entity\Recompense;
use App\Entity\Post;
use App\Entity\Commentaire;
use App\Entity\Habitude;
use App\Repository\ChallengeRepository;
use App\Repository\MentalEntryRepository;
use App\Repository\MentalTipRepository;
use App\Repository\MoodRepository;
use App\Repository\RecompenseRepository;
use App\Repository\PostRepository;
use App\Repository\CommentaireRepository;
use App\Repository\HabitudeRepository;
use App\Form\AdminUserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin')]
    public function index(
        UserRepository $userRepository, 
        ReclamationRepository $reclamationRepo,
        ChallengeRepository $challengeRepo,
        PostRepository $postRepo,
        HabitudeRepository $habitudeRepo,
        \App\Repository\ProgramRepository $programRepo,
        \App\Repository\WorkoutProgressRepository $workoutProgressRepo
    ): Response
    {
        $users = $userRepository->findAll();
        $reclamations = $reclamationRepo->findAll();
        $clients = $userRepository->findClientsWithFitnessStats();

        $stats = [
            'users_total'  => count($users),
            'users_active' => count(array_filter($users, fn(User $u) => $u->isActive())),
            'users_admins' => count(array_filter($users, fn(User $u) => in_array($u->getRole(), ['admin', 'coach']))),
            'rec_total'    => count($reclamations),
            'rec_ouvert'   => count(array_filter($reclamations, fn(Reclamation $r) => $r->getStatut() === 'ouvert')),
            'challenges'   => $challengeRepo->count([]),
            'posts'        => $postRepo->count([]),
            'habitudes'    => $habitudeRepo->count([]),
            'total_programs' => $programRepo->count([]),
            'published_programs' => $programRepo->count(['isPublished' => true]),
            'total_workouts' => $workoutProgressRepo->count([]),
            'active_clients' => count(array_filter($clients, fn($c) => $c['total_workouts'] > 0)),
        ];

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
            'clients' => $clients,
        ]);
    }

    #[Route('/fitness/export', name: 'app_admin_fitness_export')]
    public function exportFitnessData(
        UserRepository $userRepo,
        \App\Repository\ProgramRepository $programRepo,
        \App\Repository\ExerciseRepository $exerciseRepo
    ): Response {
        $clients = $userRepo->findClientsWithFitnessStats();
        $programs = $programRepo->findAll();
        $exercises = $exerciseRepo->findAll();

        $spreadsheet = new Spreadsheet();
        
        // --- SHEET 1: Clients ---
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Clients & Suivi');
        
        $sheet1->setCellValue('A1', 'ID');
        $sheet1->setCellValue('B1', 'Nom');
        $sheet1->setCellValue('C1', 'Prénom');
        $sheet1->setCellValue('D1', 'Email');
        $sheet1->setCellValue('E1', 'Téléphone');
        $sheet1->setCellValue('F1', 'Âge');
        $sheet1->setCellValue('G1', 'Workouts Trackés');
        $sheet1->setCellValue('H1', 'Last Activity');
        $sheet1->getStyle('A1:H1')->getFont()->setBold(true);

        $row = 2;
        foreach ($clients as $client) {
            $sheet1->setCellValue('A' . $row, $client['id']);
            $sheet1->setCellValue('B' . $row, $client['nom']);
            $sheet1->setCellValue('C' . $row, $client['prenom']);
            $sheet1->setCellValue('D' . $row, $client['email']);
            $sheet1->setCellValue('E' . $row, $client['telephone']);
            $sheet1->setCellValue('F' . $row, $client['age']);
            $sheet1->setCellValue('G' . $row, $client['total_workouts']);
            $sheet1->setCellValue('H' . $row, $client['last_workout_date']);
            $row++;
        }
        foreach (range('A', 'H') as $col) { $sheet1->getColumnDimension($col)->setAutoSize(true); }

        // --- SHEET 2: Programmes ---
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Programmes');
        
        $sheet2->setCellValue('A1', 'ID');
        $sheet2->setCellValue('B1', 'Titre');
        $sheet2->setCellValue('C1', 'Objectif');
        $sheet2->setCellValue('D1', 'Durée (Semaines)');
        $sheet2->setCellValue('E1', 'Niveau');
        $sheet2->setCellValue('F1', 'Statut');
        $sheet2->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($programs as $prog) {
            $sheet2->setCellValue('A' . $row, $prog->getId());
            $sheet2->setCellValue('B' . $row, $prog->getTitle());
            $sheet2->setCellValue('C' . $row, $prog->getGoal());
            $sheet2->setCellValue('D' . $row, $prog->getDurationWeeks());
            $sheet2->setCellValue('E' . $row, $prog->getLevel());
            $sheet2->setCellValue('F' . $row, $prog->isIsPublished() ? 'Publié' : 'Brouillon');
            $row++;
        }
        foreach (range('A', 'F') as $col) { $sheet2->getColumnDimension($col)->setAutoSize(true); }

        // --- SHEET 3: Exercices ---
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Exercices');
        
        $sheet3->setCellValue('A1', 'ID');
        $sheet3->setCellValue('B1', 'Nom');
        $sheet3->setCellValue('C1', 'Catégorie');
        $sheet3->setCellValue('D1', 'Difficulté');
        $sheet3->setCellValue('E1', 'Durée (min)');
        $sheet3->setCellValue('F1', 'Calories');
        $sheet3->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($exercises as $ex) {
            $sheet3->setCellValue('A' . $row, $ex->getId());
            $sheet3->setCellValue('B' . $row, $ex->getName());
            $sheet3->setCellValue('C' . $row, $ex->getCategory());
            $sheet3->setCellValue('D' . $row, $ex->getDifficulty());
            $sheet3->setCellValue('E' . $row, $ex->getDuration());
            $sheet3->setCellValue('F' . $row, $ex->getCalories());
            $row++;
        }
        foreach (range('A', 'F') as $col) { $sheet3->getColumnDimension($col)->setAutoSize(true); }

        // Reset view to the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xls($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;filename="fitness_complet_export.xls"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $stats = [
            'total'  => count($users),
            'admins' => count(array_filter($users, fn(User $u) => in_array($u->getRole(), ['admin', 'coach'], true))),
            'active' => count(array_filter($users, fn(User $u) => $u->isActive())),
        ];

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    #[Route('/reclamations', name: 'app_admin_reclamations')]
    public function reclamations(ReclamationRepository $reclamationRepo): Response
    {
        $reclamations = $reclamationRepo->findBy([], ['createdAt' => 'DESC']);
        $stats = [
            'total'   => count($reclamations),
            'ouvert'  => count(array_filter($reclamations, fn(Reclamation $r) => $r->getStatut() === 'ouvert')),
            'resolu'  => count(array_filter($reclamations, fn(Reclamation $r) => $r->getStatut() === 'resolu')),
        ];

        return $this->render('admin/reclamations.html.twig', [
            'reclamations' => $reclamations,
            'stats'        => $stats,
        ]);
    }


    #[Route('/user/{id}/toggle', name: 'app_admin_toggle_user', methods: ['POST'])]
    public function toggleUser(User $user, EntityManagerInterface $em): Response
    {
        $user->setActive(!$user->isActive());
        $em->flush();

        $this->addFlash('success', sprintf(
            'Utilisateur %s %s.',
            $user->getPrenom(),
            $user->isActive() ? 'activé' : 'désactivé'
        ));

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/role', name: 'app_admin_change_role', methods: ['POST'])]
    public function changeRole(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $role = $request->request->get('role');
        if (in_array($role, ['user', 'coach', 'admin'], true)) {
            $user->setRole($role);
            $em->flush();
            $this->addFlash('success', 'Rôle mis à jour avec succès.');

            // Security: If current admin downgrades THEMSELVES, log them out
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            if ($user->getId() === $currentUser->getId() && !$user->isAdmin()) {
                $this->addFlash('warning', 'Vos droits ont été modifiés. Veuillez vous reconnecter.');
                return $this->redirectToRoute('app_logout');
            }
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function editUser(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Utilisateur "' . $user->getPrenom() . '" mis à jour.');

            // Security: If current admin downgrades THEMSELVES, log them out
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            if ($user->getId() === $currentUser->getId() && !$user->isAdmin()) {
                return $this->redirectToRoute('app_logout');
            }

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/{id}/delete', name: 'app_admin_delete_user', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/reclamation/{id}/statut', name: 'app_admin_reclamation_statut', methods: ['POST'])]
    public function changeStatutReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $statut = $request->request->get('statut');
        if (in_array($statut, ['ouvert', 'en_cours', 'resolu', 'ferme'], true)) {
            $reclamation->setStatut($statut);
            $reclamation->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'Statut de la réclamation mis à jour.');
        }

        return $this->redirectToRoute('app_admin_reclamations');
    }

    #[Route('/reclamation/{id}/delete', name: 'app_admin_reclamation_delete', methods: ['POST'])]
    public function deleteReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_rec'.$reclamation->getId(), $request->request->get('_token'))) {
            $em->remove($reclamation);
            $em->flush();
            $this->addFlash('success', 'Réclamation supprimée.');
        }

        return $this->redirectToRoute('app_admin_reclamations');
    }

    #[Route('/sante-mentale', name: 'app_admin_sante_mentale')]
    public function santeMentale(
        MoodRepository $moodRepository,
        MentalTipRepository $mentalTipRepository,
        MentalEntryRepository $mentalEntryRepository,
    ): Response {
        $entries = $mentalEntryRepository->findForDashboard();
        $tips = $mentalTipRepository->findForDashboard();

        $stats = [
            'moods' => $moodRepository->count([]),
            'tips' => $mentalTipRepository->count([]),
            'entries' => count($entries),
            'high_stress' => count(array_filter($entries, static fn (MentalEntry $e): bool => ($e->getEmotionLevel() ?? 0) >= 8)),
        ];

        $entriesByMood = [];
        foreach ($entries as $entry) {
            $name = $entry->getMood()?->getMoodName() ?? '—';
            $entriesByMood[$name] = ($entriesByMood[$name] ?? 0) + 1;
        }

        $tipsByMood = [];
        foreach ($tips as $tip) {
            $name = $tip->getMood()?->getMoodName() ?? '—';
            $tipsByMood[$name] = ($tipsByMood[$name] ?? 0) + 1;
        }

        return $this->render('admin/sante_mentale.html.twig', [
            'entries' => $entries,
            'stats' => $stats,
            'entries_by_mood' => $entriesByMood,
            'tips_by_mood' => $tipsByMood,
        ]);
    }

    #[Route('/motivation', name: 'app_admin_motivation')]
    public function motivationDashboard(
    ChallengeRepository $challengeRepo,
    UserRepository $userRepo,
    RecompenseRepository $recompenseRepo,
    EntityManagerInterface $em
): Response {
    $challenges  = $challengeRepo->findAll();
    $coaches     = $userRepo->findBy(['role' => 'coach']);
    $recompenses = $recompenseRepo->findAll();

    // ── Stats de base ──
    $stats = [
        'ch_total'    => count($challenges),
        'ch_active'   => count(array_filter($challenges, fn(Challenge $c) => $c->getStatut() === 'actif')),
        'ch_done'     => count(array_filter($challenges, fn(Challenge $c) => $c->getStatut() === 'termine')),
        'ch_annule'   => count(array_filter($challenges, fn(Challenge $c) => $c->getStatut() === 'annule')),
        'co_total'    => count($coaches),
        'co_active'   => count(array_filter($coaches, fn(User $u) => $u->isActive())),
        'rec_total'   => count($recompenses),
        'rec_points'  => array_reduce($recompenses, fn($carry, Recompense $r) => $carry + $r->getPoints(), 0),
        'rec_ouvert'  => $em->getRepository(\App\Entity\Reclamation::class)->count(['statut' => 'ouvert']),
    ];

    // ── Chart 1 : Challenges par statut (Doughnut) ──
    $chartStatuts = [
        'labels' => ['Actifs', 'Terminés', 'Annulés'],
        'data'   => [$stats['ch_active'], $stats['ch_done'], $stats['ch_annule']],
        'colors' => ['#6c63ff', '#22d3a5', '#ef4444'],
    ];

    // ── Chart 2 : Challenges par catégorie (Bar) ──
    $catData = [];
    foreach ($challenges as $c) {
        $cat = $c->getCategorie()?->getNom() ?? 'Sans catégorie';
        $catData[$cat] = ($catData[$cat] ?? 0) + 1;
    }
    arsort($catData);
    $catData = array_slice($catData, 0, 8, true);
    $chartCategories = [
        'labels' => array_keys($catData),
        'data'   => array_values($catData),
    ];

    // ── Chart 3 : Challenges créés par mois (Line, 6 derniers mois) ──
    $monthData = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = (new \DateTime("first day of -$i months"))->format('Y-m');
        $monthData[$month] = 0;
    }
    foreach ($challenges as $c) {
        $m = $c->getCreatedAt()->format('Y-m');
        if (isset($monthData[$m])) {
            $monthData[$m]++;
        }
    }
    $chartTimeline = [
        'labels' => array_map(fn($k) => (new \DateTime($k . '-01'))->format('M Y'), array_keys($monthData)),
        'data'   => array_values($monthData),
    ];

    // ── Chart 4 : Récompenses par points (Horizontal Bar) ──
    $sortedRecs = $recompenses;
    usort($sortedRecs, fn($a, $b) => $b->getPoints() - $a->getPoints());
    $topRecs = array_slice($sortedRecs, 0, 6);
    $chartRecompenses = [
        'labels' => array_map(fn($r) => mb_substr($r->getNom(), 0, 20), $topRecs),
        'data'   => array_map(fn($r) => $r->getPoints(), $topRecs),
    ];

    // ── Chart 5 : Coaches actifs vs inactifs (Polar Area) ──
    $chartCoaches = [
        'labels' => ['Actifs', 'Inactifs'],
        'data'   => [$stats['co_active'], $stats['co_total'] - $stats['co_active']],
        'colors' => ['rgba(108,99,255,0.8)', 'rgba(239,68,68,0.6)'],
    ];

    return $this->render('admin/motivation.html.twig', [
        'stats'              => $stats,
        'challenges'         => $challenges,
        'coaches'            => $coaches,
        'recompenses'        => $recompenses,
        'chartStatuts'       => $chartStatuts,
        'chartCategories'    => $chartCategories,
        'chartTimeline'      => $chartTimeline,
        'chartRecompenses'   => $chartRecompenses,
        'chartCoaches'       => $chartCoaches,
    ]);
    }

    #[Route('/posts', name: 'app_admin_posts')]
    public function postsDashboard(
        PostRepository $postRepo,
        CommentaireRepository $commentRepo,
        EntityManagerInterface $em
    ): Response {
        $posts = $postRepo->findBy([], ['createdAt' => 'DESC']);
        $comments = $commentRepo->findAll();

        $stats = [
            'total_posts'    => count($posts),
            'total_comments' => count($comments),
            'anonymous'      => count(array_filter($posts, fn(Post $p) => $p->isIsAnonymous())),
            'total_likes'    => array_reduce($posts, fn($carry, Post $p) => $carry + $p->countLikes(), 0),
        ];

        return $this->render('admin/posts.html.twig', [
            'stats' => $stats,
            'posts' => $posts,
        ]);
    }

    #[Route('/posts/{id}/delete', name: 'app_admin_post_delete', methods: ['POST'])]
    public function deletePost(Request $request, Post $post, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_admin_post'.$post->getId(), $request->request->get('_token'))) {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'Le post a été supprimé par l\'administrateur.');
        }
        return $this->redirectToRoute('app_admin_posts');
    }

    #[Route('/comment/{id}/delete', name: 'app_admin_comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, Commentaire $comment, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_admin_comment'.$comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Le commentaire a été supprimé.');
        }
        return $this->redirectToRoute('app_admin_posts');
    }

    #[Route('/habitudes', name: 'app_admin_habitudes')]
    public function habitudesDashboard(HabitudeRepository $habitudeRepo): Response
    {
        $habitudes = $habitudeRepo->findBy([], ['startDate' => 'DESC']);

        $stats = [
            'total' => count($habitudes),
            'active' => count(array_filter($habitudes, fn(Habitude $h) => $h->isActive())),
            'completed' => count(array_filter($habitudes, fn(Habitude $h) => !$h->isActive())),
        ];

        return $this->render('admin/habitudes.html.twig', [
            'habitudes' => $habitudes,
            'stats'     => $stats,
        ]);
    }

    #[Route('/habitudes/{id}/delete', name: 'app_admin_habitude_delete', methods: ['POST'])]
    public function deleteHabitude(Request $request, Habitude $habitude, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_admin_habitude'.$habitude->getId(), $request->request->get('_token'))) {
            $em->remove($habitude);
            $em->flush();
            $this->addFlash('success', 'L\'habitude a été supprimée par l\'administrateur.');
        }
        return $this->redirectToRoute('app_admin_habitudes');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STATISTIQUES
    // ──────────────────────────────────────────────────────────────────────────

    #[Route('/statistiques', name: 'app_admin_statistiques')]
    public function statistiques(
        UserRepository        $userRepo,
        ReclamationRepository $recRepo,
        ChallengeRepository   $challengeRepo,
        PostRepository        $postRepo,
        HabitudeRepository    $habitudeRepo,
        ChartBuilderInterface $chartBuilder
    ): Response {
        // ── 1. Inscriptions par mois (6 derniers mois) ─ Line chart ─────────
        $regData   = $userRepo->registrationsPerMonth(6);
        $regMonths = array_map(
            fn($k) => (new \DateTime($k . '-01'))->format('M Y'),
            array_keys($regData)
        );

        $chartRegistrations = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chartRegistrations->setData([
            'labels'   => $regMonths,
            'datasets' => [[
                'label'           => 'Inscriptions',
                'data'            => array_values($regData),
                'borderColor'     => '#6c63ff',
                'backgroundColor' => 'rgba(108,99,255,0.12)',
                'fill'            => true,
                'tension'         => 0.4,
                'pointBackgroundColor' => '#6c63ff',
                'pointRadius'     => 5,
            ]],
        ]);
        $chartRegistrations->setOptions([
            'responsive' => true,
            'plugins'    => ['legend' => ['display' => false]],
            'scales'     => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1,
                    'color' => '#94a3b8'], 'grid' => ['color' => 'rgba(255,255,255,0.05)']],
                'x' => ['ticks' => ['color' => '#94a3b8'], 'grid' => ['display' => false]],
            ],
        ]);

        // ── 2. Utilisateurs par rôle ─ Doughnut chart ───────────────────
        $roleRows  = $userRepo->countByRole();
        $roleMap   = ['user' => 'Utilisateurs', 'coach' => 'Coachs', 'admin' => 'Admins'];
        $roleLabels = array_map(fn($r) => $roleMap[$r['role']] ?? ucfirst($r['role']), $roleRows);
        $roleData   = array_map(fn($r) => (int)$r['cnt'], $roleRows);

        $chartRoles = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chartRoles->setData([
            'labels'   => $roleLabels,
            'datasets' => [[
                'data'            => $roleData,
                'backgroundColor' => ['#6c63ff', '#00d2c8', '#f59e0b', '#ef4444'],
                'borderWidth'     => 0,
                'hoverOffset'     => 6,
            ]],
        ]);
        $chartRoles->setOptions([
            'responsive' => true,
            'cutout'     => '65%',
            'plugins'    => ['legend' => ['position' => 'bottom',
                'labels' => ['color' => '#94a3b8', 'padding' => 16, 'usePointStyle' => true]]],
        ]);

        // ── 3. Réclamations par statut ─ Bar chart ─────────────────────
        $allRec    = $recRepo->findAll();
        $recStatuts = ['ouvert' => 0, 'en_cours' => 0, 'resolu' => 0, 'ferme' => 0];
        foreach ($allRec as $r) {
            $s = $r->getStatut();
            if (isset($recStatuts[$s])) $recStatuts[$s]++;
        }

        $chartReclamations = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chartReclamations->setData([
            'labels'   => ['Ouvert', 'En cours', 'Résolu', 'Fermé'],
            'datasets' => [[
                'label'           => 'Réclamations',
                'data'            => array_values($recStatuts),
                'backgroundColor' => ['rgba(239,68,68,0.7)', 'rgba(245,158,11,0.7)',
                                      'rgba(34,211,165,0.7)', 'rgba(100,116,139,0.7)'],
                'borderRadius'    => 8,
                'borderSkipped'   => false,
            ]],
        ]);
        $chartReclamations->setOptions([
            'responsive' => true,
            'plugins'    => ['legend' => ['display' => false]],
            'scales'     => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1,
                    'color' => '#94a3b8'], 'grid' => ['color' => 'rgba(255,255,255,0.05)']],
                'x' => ['ticks' => ['color' => '#94a3b8'], 'grid' => ['display' => false]],
            ],
        ]);

        // ── 4. Habitudes par catégorie ─ Polar Area chart ──────────────
        $allHabitudes = $habitudeRepo->findAll();
        $habCats = [];
        foreach ($allHabitudes as $h) {
            $cat = $h->getCategory() ?: 'Autre';
            $habCats[$cat] = ($habCats[$cat] ?? 0) + 1;
        }
        arsort($habCats);

        $chartHabitudes = $chartBuilder->createChart(Chart::TYPE_POLAR_AREA);
        $chartHabitudes->setData([
            'labels'   => array_keys($habCats),
            'datasets' => [[
                'data'            => array_values($habCats),
                'backgroundColor' => [
                    'rgba(108,99,255,0.7)', 'rgba(0,210,200,0.7)', 'rgba(245,158,11,0.7)',
                    'rgba(239,68,68,0.7)', 'rgba(34,211,165,0.7)', 'rgba(236,72,153,0.7)',
                ],
                'borderWidth' => 0,
            ]],
        ]);
        $chartHabitudes->setOptions([
            'responsive' => true,
            'plugins'    => ['legend' => ['position' => 'bottom',
                'labels' => ['color' => '#94a3b8', 'padding' => 12, 'usePointStyle' => true]]],
            'scales'     => ['r' => ['ticks' => ['backdropColor' => 'transparent', 'color' => '#64748b'],
                'grid' => ['color' => 'rgba(255,255,255,0.06)']]],
        ]);

        // ── 5. Posts vs Challenges vs Habitudes ─ Bar (grouped) chart ──
        $totalPosts      = $postRepo->count([]);
        $totalChallenges = $challengeRepo->count([]);
        $totalHabitudes  = $habitudeRepo->count([]);
        $totalUsers      = count($userRepo->findAll());
        $totalRec        = count($allRec);

        $chartOverview = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chartOverview->setData([
            'labels'   => ['Utilisateurs', 'Posts', 'Challenges', 'Habitudes', 'Réclamations'],
            'datasets' => [[
                'label'           => 'Total',
                'data'            => [$totalUsers, $totalPosts, $totalChallenges, $totalHabitudes, $totalRec],
                'backgroundColor' => [
                    'rgba(108,99,255,0.75)',
                    'rgba(0,210,200,0.75)',
                    'rgba(245,158,11,0.75)',
                    'rgba(34,211,165,0.75)',
                    'rgba(239,68,68,0.75)',
                ],
                'borderRadius'  => 10,
                'borderSkipped' => false,
            ]],
        ]);
        $chartOverview->setOptions([
            'responsive'          => true,
            'indexAxis'           => 'y',
            'plugins'             => ['legend' => ['display' => false]],
            'scales'              => [
                'x' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1,
                    'color' => '#94a3b8'], 'grid' => ['color' => 'rgba(255,255,255,0.05)']],
                'y' => ['ticks' => ['color' => '#94a3b8'], 'grid' => ['display' => false]],
            ],
        ]);

        return $this->render('admin/statistiques.html.twig', [
            'totalUsers'      => $totalUsers,
            'totalPosts'      => $totalPosts,
            'totalChallenges' => $totalChallenges,
            'totalHabitudes'  => $totalHabitudes,
            'totalRec'        => $totalRec,
            'chartRegistrations' => $chartRegistrations,
            'chartRoles'         => $chartRoles,
            'chartReclamations'  => $chartReclamations,
            'chartHabitudes'     => $chartHabitudes,
            'chartOverview'      => $chartOverview,
        ]);
    }
}


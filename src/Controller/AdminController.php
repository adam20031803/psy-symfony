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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin')]
    public function index(
        UserRepository $userRepository, 
        ReclamationRepository $reclamationRepo,
        ChallengeRepository $challengeRepo,
        PostRepository $postRepo,
        HabitudeRepository $habitudeRepo
    ): Response
    {
        $users = $userRepository->findAll();
        $reclamations = $reclamationRepo->findAll();

        $stats = [
            'users_total'  => count($users),
            'users_active' => count(array_filter($users, fn(User $u) => $u->isActive())),
            'users_admins' => count(array_filter($users, fn(User $u) => in_array($u->getRole(), ['admin', 'coach']))),
            'rec_total'    => count($reclamations),
            'rec_ouvert'   => count(array_filter($reclamations, fn(Reclamation $r) => $r->getStatut() === 'ouvert')),
            'challenges'   => $challengeRepo->count([]),
            'posts'        => $postRepo->count([]),
            'habitudes'    => $habitudeRepo->count([]),
        ];

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
        ]);
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
}


<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\User;
use App\Repository\ReclamationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Challenge;
use App\Entity\Recompense;
use App\Entity\Post;
use App\Entity\Commentaire;
use App\Entity\Habitude;
use App\Repository\ChallengeRepository;
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

        $stats = [
            'ch_total'    => count($challenges),
            'ch_active'   => count(array_filter($challenges, fn(Challenge $c) => $c->getStatut() === 'actif')),
            'ch_done'     => count(array_filter($challenges, fn(Challenge $c) => $c->getStatut() === 'termine')),
            'co_total'    => count($coaches),
            'co_active'   => count(array_filter($coaches, fn(User $u) => $u->isActive())),
            'rec_total'   => count($recompenses),
            'rec_points'  => array_reduce($recompenses, fn($carry, Recompense $r) => $carry + $r->getPoints(), 0),
            'rec_ouvert'  => $em->getRepository(\App\Entity\Reclamation::class)->count(['statut' => 'ouvert']),
        ];

        return $this->render('admin/motivation.html.twig', [
            'stats'       => $stats,
            'challenges'  => $challenges,
            'coaches'     => $coaches,
            'recompenses' => $recompenses,
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


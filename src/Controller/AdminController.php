<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\User;
use App\Repository\ReclamationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function index(UserRepository $userRepository, ReclamationRepository $reclamationRepo): Response
    {
        $users        = $userRepository->findAll();
        $reclamations = $reclamationRepo->findBy([], ['createdAt' => 'DESC']);

        $stats = [
            'total'       => count($users),
            'admins'      => count(array_filter($users, fn(User $u) => $u->getRole() === 'admin')),
            'active'      => count(array_filter($users, fn(User $u) => $u->isActive())),
            'rec_total'   => count($reclamations),
            'rec_ouvert'  => count(array_filter($reclamations, fn(Reclamation $r) => $r->getStatut() === 'ouvert')),
            'rec_resolu'  => count(array_filter($reclamations, fn(Reclamation $r) => $r->getStatut() === 'resolu')),
        ];

        return $this->render('admin/index.html.twig', [
            'users'        => $users,
            'reclamations' => $reclamations,
            'stats'        => $stats,
        ]);
    }

    #[Route('/user/{id}/toggle', name: 'app_admin_toggle_user', methods: ['POST'])]
    public function toggleUser(User $user, EntityManagerInterface $em): Response
    {
        $user->setIsActive(!$user->isActive());
        $em->flush();

        $this->addFlash('success', sprintf(
            'Utilisateur %s %s.',
            $user->getPrenom(),
            $user->isActive() ? 'activé' : 'désactivé'
        ));

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/user/{id}/role', name: 'app_admin_change_role', methods: ['POST'])]
    public function changeRole(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $role = $request->request->get('role');
        if (in_array($role, ['user', 'coach', 'admin'], true)) {
            $user->setRole($role);
            $em->flush();
            $this->addFlash('success', 'Rôle mis à jour avec succès.');
        }

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/user/{id}/delete', name: 'app_admin_delete_user', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('app_admin');
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

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/reclamation/{id}/delete', name: 'app_admin_reclamation_delete', methods: ['POST'])]
    public function deleteReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_rec'.$reclamation->getId(), $request->request->get('_token'))) {
            $em->remove($reclamation);
            $em->flush();
            $this->addFlash('success', 'Réclamation supprimée.');
        }

        return $this->redirectToRoute('app_admin');
    }
}


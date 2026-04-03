<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/reclamation')]
class ReclamationController extends AbstractController
{
    /** Liste des réclamations de l'utilisateur connecté */
    #[Route('', name: 'app_reclamation_index', methods: ['GET'])]
    public function index(ReclamationRepository $repo): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $reclamations = $repo->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('reclamation/index.html.twig', [
            'user'         => $user,
            'reclamations' => $reclamations,
        ]);
    }

    /** Formulaire de création d'une réclamation */
    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $error = null;

        if ($request->isMethod('POST')) {
            $sujet       = trim($request->request->get('sujet', ''));
            $description = trim($request->request->get('description', ''));

            if (empty($sujet) || empty($description)) {
                $error = 'Veuillez remplir tous les champs obligatoires.';
            } elseif (strlen($sujet) > 255) {
                $error = 'Le sujet ne doit pas dépasser 255 caractères.';
            } else {
                $rec = new Reclamation();
                $rec->setUser($user);
                $rec->setSujet($sujet);
                $rec->setDescription($description);

                $em->persist($rec);
                $em->flush();

                $this->addFlash('success', 'Votre réclamation a bien été envoyée. Nous vous répondrons dans les plus brefs délais.');
                return $this->redirectToRoute('app_reclamation_index');
            }
        }

        return $this->render('reclamation/new.html.twig', [
            'user'  => $user,
            'error' => $error,
        ]);
    }
}

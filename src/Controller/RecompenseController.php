<?php

namespace App\Controller;

use App\Entity\Recompense;
use App\Repository\RecompenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/recompense')]
class RecompenseController extends AbstractController
{
    #[Route('/', name: 'app_recompense_index', methods: ['GET'])]
    public function index(Request $request, RecompenseRepository $recompenseRepository): Response
    {
        $keyword = $request->query->get('search', '');
        $sort    = $request->query->get('sort', 'nom_asc');
        
        $qb = $recompenseRepository->createQueryBuilder('r');

        if ($keyword) {
            $qb->where('r.nom LIKE :kw OR r.description LIKE :kw')
               ->setParameter('kw', '%' . $keyword . '%');
        }

        switch ($sort) {
            case 'points_asc':  $qb->orderBy('r.points', 'ASC'); break;
            case 'points_desc': $qb->orderBy('r.points', 'DESC'); break;
            default:            $qb->orderBy('r.nom', 'ASC'); break;
        }

        $recompenses = $qb->getQuery()->getResult();

        return $this->render('recompense/index.html.twig', [
            'recompenses' => $recompenses,
            'filters' => [
                'search' => $keyword,
                'sort' => $sort,
            ],
            'stats' => [
                'total' => count($recompenses),
            ]
        ]);
    }

    #[Route('/new', name: 'app_recompense_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $recompense = new Recompense();
        $recompense->setNom($request->request->get('nom', ''));
        $recompense->setDescription($request->request->get('description', ''));
        $recompense->setPoints((int)($request->request->get('points', 0)));
        
        $image = $request->request->get('image');
        if ($image) $recompense->setImage($image);

        // ── Server-side validation ──
        $errors = $validator->validate($recompense);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash('error', '❌ ' . implode(' | ', $errorMessages));
            return $this->redirectToRoute('app_recompense_index');
        }

        $entityManager->persist($recompense);
        $entityManager->flush();

        $this->addFlash('success', '🎁 Récompense créée avec succès!');
        return $this->redirectToRoute('app_recompense_index');
    }

    #[Route('/{id}/edit', name: 'app_recompense_edit', methods: ['POST'])]
    public function edit(Request $request, Recompense $recompense, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $recompense->setNom($request->request->get('nom', $recompense->getNom()));
        $recompense->setDescription($request->request->get('description', $recompense->getDescription()));
        $recompense->setPoints((int)($request->request->get('points', $recompense->getPoints())));
        
        $image = $request->request->get('image');
        if ($image !== null) $recompense->setImage($image);

        // ── Server-side validation ──
        $errors = $validator->validate($recompense);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash('error', '❌ ' . implode(' | ', $errorMessages));
            return $this->redirectToRoute('app_recompense_index');
        }

        $entityManager->flush();

        $this->addFlash('success', '✏️ Récompense modifiée avec succès!');
        return $this->redirectToRoute('app_recompense_index');
    }

    #[Route('/{id}/delete', name: 'app_recompense_delete', methods: ['POST'])]
    public function delete(Request $request, Recompense $recompense, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recompense->getId(), $request->request->get('_token'))) {
            // Remove all challenge_recompense links first to avoid FK constraint
            $linkedRecords = $entityManager->getRepository(\App\Entity\ChallengeRecompense::class)
                ->findBy(['recompense' => $recompense]);
            foreach ($linkedRecords as $link) {
                $entityManager->remove($link);
            }

            $entityManager->remove($recompense);
            $entityManager->flush();
            $this->addFlash('success', '🗑️ Récompense supprimée!');
        }

        return $this->redirectToRoute('app_recompense_index');
    }
}

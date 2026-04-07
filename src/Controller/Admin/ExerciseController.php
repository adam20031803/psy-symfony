<?php

namespace App\Controller\Admin;

use App\Entity\Exercise;
use App\Form\ExerciseType;
use App\Repository\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/exercise', name: 'admin_exercise_')]
class ExerciseController extends AbstractController
{
    // ---------------------------------------------------------------
    // LIST — GET /admin/exercise
    // ---------------------------------------------------------------
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ExerciseRepository $repo): Response
    {
        return $this->render('admin/exercise/index.html.twig', [
            'exercises' => $repo->findAll(),
        ]);
    }

    // ---------------------------------------------------------------
    // NEW — GET|POST /admin/exercise/new
    // ---------------------------------------------------------------
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $exercise = new Exercise();
        $form = $this->createForm(ExerciseType::class, $exercise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($exercise);
            $em->flush();

            $this->addFlash('success', 'Exercice créé avec succès !');
            return $this->redirectToRoute('admin_exercise_index');
        }

        return $this->render('admin/exercise/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ---------------------------------------------------------------
    // SHOW — GET /admin/exercise/{id}
    // ---------------------------------------------------------------
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Exercise $exercise): Response
    {
        return $this->render('admin/exercise/show.html.twig', [
            'exercise' => $exercise,
        ]);
    }

    // ---------------------------------------------------------------
    // EDIT — GET|POST /admin/exercise/{id}/edit
    // ---------------------------------------------------------------
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Exercise $exercise, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ExerciseType::class, $exercise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Exercice modifié avec succès !');
            return $this->redirectToRoute('admin_exercise_index');
        }

        return $this->render('admin/exercise/edit.html.twig', [
            'exercise' => $exercise,
            'form'     => $form->createView(),
        ]);
    }

    // ---------------------------------------------------------------
    // DELETE — POST /admin/exercise/{id}/delete
    // ---------------------------------------------------------------
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Exercise $exercise, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_exercise_' . $exercise->getId(), (string) $request->request->get('_token'))) {
            $em->remove($exercise);
            $em->flush();
            $this->addFlash('danger', 'Exercice supprimé.');
        }

        return $this->redirectToRoute('admin_exercise_index');
    }
}

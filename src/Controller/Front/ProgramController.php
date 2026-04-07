<?php

namespace App\Controller\Front;

use App\Entity\Program;
use App\Entity\ProgramAssignment;
use App\Repository\ProgramAssignmentRepository;
use App\Repository\ProgramRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fitness/programs', name: 'front_program_')]
class ProgramController extends AbstractController
{
    // -------------------------------------------------------------------
    // PROGRAM LIST — GET /fitness/programs
    // Shows only published programs
    // -------------------------------------------------------------------
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(ProgramRepository $repo): Response
    {
        return $this->render('front/program/list.html.twig', [
            'programs' => $repo->findPublished(),
        ]);
    }

    // -------------------------------------------------------------------
    // PROGRAM SHOW — GET /fitness/programs/{id}
    // Only shows published programs to the public
    // -------------------------------------------------------------------
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Program $program): Response
    {
        if (!$program->isPublished()) {
            $this->addFlash('warning', 'Ce programme n\'est pas encore disponible.');
            return $this->redirectToRoute('front_program_list');
        }

        return $this->render('front/program/show.html.twig', [
            'program' => $program,
        ]);
    }

    // -------------------------------------------------------------------
    // MY PROGRAMS — GET /fitness/my-programs
    // Shows programs assigned to the logged-in user
    // -------------------------------------------------------------------
    #[Route('/my-programs', name: 'my_programs', methods: ['GET'])]
    public function myPrograms(ProgramAssignmentRepository $assignRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();

        $assignments = $assignRepo->findByUser($user);

        return $this->render('front/program/my_programs.html.twig', [
            'assignments' => $assignments,
        ]);
    }

    // -------------------------------------------------------------------
    // ACCEPT — POST /fitness/my-programs/{id}/accept
    // -------------------------------------------------------------------
    #[Route('/my-programs/{id}/accept', name: 'accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function accept(
        ProgramAssignment      $assignment,
        Request                $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('assignment_' . $assignment->getId(), (string) $request->request->get('_token'))) {
            $assignment->setStatus(ProgramAssignment::STATUS_ACCEPTED);
            $em->flush();
            $this->addFlash('success', 'Programme accepté ! Bonne séance 💪');
        }

        return $this->redirectToRoute('front_program_my_programs');
    }

    // -------------------------------------------------------------------
    // DECLINE — POST /fitness/my-programs/{id}/decline
    // -------------------------------------------------------------------
    #[Route('/my-programs/{id}/decline', name: 'decline', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function decline(
        ProgramAssignment      $assignment,
        Request                $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($assignment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('assignment_' . $assignment->getId(), (string) $request->request->get('_token'))) {
            $assignment->setStatus(ProgramAssignment::STATUS_DECLINED);
            $em->flush();
            $this->addFlash('warning', 'Programme décliné.');
        }

        return $this->redirectToRoute('front_program_my_programs');
    }
}

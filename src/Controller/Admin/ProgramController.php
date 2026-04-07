<?php

namespace App\Controller\Admin;

use App\Entity\Program;
use App\Entity\ProgramAssignment;
use App\Form\ProgramType;
use App\Repository\ProgramAssignmentRepository;
use App\Repository\ProgramRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/program', name: 'admin_program_')]
class ProgramController extends AbstractController
{
    // ---------------------------------------------------------------
    // LIST — GET /admin/program
    // ---------------------------------------------------------------
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request                   $request,
        ProgramRepository         $repo,
        ProgramAssignmentRepository $assignRepo
    ): Response {
        $searchQuery = $request->query->get('q', '');
        
        $programs = $searchQuery
            ? $repo->searchByQuery($searchQuery)
            : $repo->findAll();

        return $this->render('admin/program/index.html.twig', [
            'programs'    => $programs,
            'stats'       => $assignRepo->buildStatsMap($programs),
            'searchQuery' => $searchQuery,
        ]);
    }

    // ---------------------------------------------------------------
    // NEW — GET|POST /admin/program/new
    // ---------------------------------------------------------------
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request                     $request,
        EntityManagerInterface      $em,
        UserRepository              $userRepo,
        ProgramAssignmentRepository $assignRepo
    ): Response {
        $program = new Program();
        $form = $this->createForm(ProgramType::class, $program);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($program);
            $em->flush(); // Need ID before creating assignments

            // --- Handle user assignment ---
            $sendTo      = $request->request->get('sendTo', 'none'); // none|all|specific
            $targetIds   = $request->request->all('targetUsers');    // array of user IDs

            try {
                if ($sendTo === 'all') {
                    $users = $userRepo->findBy(['role' => 'user']);
                    $this->createAssignments($em, $program, $users);
                } elseif ($sendTo === 'specific' && !empty($targetIds)) {
                    $users = $userRepo->findBy(['id' => $targetIds]);
                    $this->createAssignments($em, $program, $users);
                }

                $em->flush();
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de l\'assignation: ' . $e->getMessage());
            }

            $this->addFlash('success', 'Programme créé avec succès !');
            return $this->redirectToRoute('admin_program_index');
        }

        // All regular users for the targeting select
        $allUsers = $userRepo->findBy(['role' => 'user'], ['nom' => 'ASC']);

        return $this->render('admin/program/new.html.twig', [
            'form'     => $form->createView(),
            'allUsers' => $allUsers,
        ]);
    }

    // ---------------------------------------------------------------
    // SHOW — GET /admin/program/{id}
    // ---------------------------------------------------------------
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Program $program, ProgramAssignmentRepository $assignRepo): Response
    {
        return $this->render('admin/program/show.html.twig', [
            'program'     => $program,
            'assignments' => $assignRepo->findByProgramWithUsers($program),
            'stats'       => [
                'total'    => $assignRepo->countTotal($program),
                'accepted' => $assignRepo->countAccepted($program),
                'declined' => $assignRepo->countDeclined($program),
            ],
        ]);
    }

    // ---------------------------------------------------------------
    // EDIT — GET|POST /admin/program/{id}/edit
    // ---------------------------------------------------------------
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request                $request,
        Program                $program,
        EntityManagerInterface $em,
        UserRepository         $userRepo,
        ProgramAssignmentRepository $assignRepo
    ): Response {
        $form = $this->createForm(ProgramType::class, $program);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            // --- Handle user assignment ---
            $sendTo      = $request->request->get('sendTo', 'none'); // none|all|specific
            $targetIds   = $request->request->all('targetUsers');    // array of user IDs

            try {
                if ($sendTo === 'all') {
                    $users = $userRepo->findBy(['role' => 'user']);
                    $this->createAssignments($em, $program, $users);
                } elseif ($sendTo === 'specific' && !empty($targetIds)) {
                    $users = $userRepo->findBy(['id' => $targetIds]);
                    $this->createAssignments($em, $program, $users);
                }

                $em->flush();
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de l\'assignation: ' . $e->getMessage());
            }

            $this->addFlash('success', 'Programme modifié avec succès !');
            return $this->redirectToRoute('admin_program_index');
        }

        // All regular users for the targeting select
        $allUsers = $userRepo->findBy(['role' => 'user'], ['nom' => 'ASC']);
        
        // Get existing assignments for this program
        $existingAssignments = $assignRepo->findByProgramWithUsers($program);
        $assignedUserIds = array_map(fn($a) => $a->getUser()->getId(), $existingAssignments);

        return $this->render('admin/program/edit.html.twig', [
            'program'           => $program,
            'form'              => $form->createView(),
            'allUsers'          => $allUsers,
            'assignments'       => $existingAssignments,
            'assignedUserIds'   => $assignedUserIds,
        ]);
    }

    // ---------------------------------------------------------------
    // DELETE — POST /admin/program/{id}/delete
    // ---------------------------------------------------------------
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Program $program, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_program_' . $program->getId(), (string) $request->request->get('_token'))) {
            $em->remove($program);
            $em->flush();
            $this->addFlash('danger', 'Programme supprimé.');
        }

        return $this->redirectToRoute('admin_program_index');
    }

    // ---------------------------------------------------------------
    // PUBLISH TOGGLE — POST /admin/program/{id}/publish
    // ---------------------------------------------------------------
    #[Route('/{id}/publish', name: 'publish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publish(Request $request, Program $program, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('publish_program_' . $program->getId(), (string) $request->request->get('_token'))) {
            $program->setIsPublished(!$program->isPublished());
            $em->flush();

            $this->addFlash(
                $program->isPublished() ? 'success' : 'warning',
                $program->isPublished() ? 'Programme publié.' : 'Programme dépublié.'
            );
        }

        return $this->redirectToRoute('admin_program_index');
    }

    // ---------------------------------------------------------------
    // SEND TO USERS — POST /admin/program/{id}/send
    // ---------------------------------------------------------------
    #[Route('/{id}/send', name: 'send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function send(
        Request                $request,
        Program                $program,
        EntityManagerInterface $em,
        UserRepository         $userRepo
    ): Response {
        if ($this->isCsrfTokenValid('send_program_' . $program->getId(), (string) $request->request->get('_token'))) {
            $sendTo    = $request->request->get('sendTo', 'none');
            $targetIds = $request->request->all('targetUsers');

            if ($sendTo === 'all') {
                $users = $userRepo->findBy(['role' => 'user']);
                $this->createAssignments($em, $program, $users);
            } elseif ($sendTo === 'specific' && !empty($targetIds)) {
                $users = $userRepo->findBy(['id' => $targetIds]);
                $this->createAssignments($em, $program, $users);
            }

            $em->flush();
            $this->addFlash('success', 'Programme envoyé aux utilisateurs sélectionnés !');
        }

        return $this->redirectToRoute('admin_program_show', ['id' => $program->getId()]);
    }

    // ---------------------------------------------------------------
    // Helper: create assignment records (skips duplicates)
    // ---------------------------------------------------------------
    private function createAssignments(EntityManagerInterface $em, Program $program, array $users): void
    {
        // Fetch existing assignments to avoid duplicates
        $existing = $em->getRepository(ProgramAssignment::class)
            ->createQueryBuilder('a')
            ->select('IDENTITY(a.user)')
            ->where('a.program = :program')
            ->setParameter('program', $program)
            ->getQuery()
            ->getSingleColumnResult();

        $createdCount = 0;
        foreach ($users as $user) {
            if (in_array($user->getId(), $existing, true)) {
                continue;
            }
            $assignment = (new ProgramAssignment())
                ->setProgram($program)
                ->setUser($user);
            $em->persist($assignment);
            $createdCount++;
        }
        
        if ($createdCount > 0) {
            $this->addFlash('info', "{$createdCount} nouvelle(s) assignation(s) créée(s).");
        }
    }
}

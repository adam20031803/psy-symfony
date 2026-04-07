<?php

namespace App\Controller\Admin;

use App\Repository\ExerciseRepository;
use App\Repository\ProgramRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function index(
        ExerciseRepository $exerciseRepo,
        ProgramRepository  $programRepo,
    ): Response {
        // Stats per category
        $categoryStats = $exerciseRepo->countByCategory();

        // Total counts
        $totalExercises = $exerciseRepo->count([]);
        $totalPrograms  = $programRepo->count([]);
        $publishedCount = $programRepo->countPublished();
        $draftCount     = $programRepo->countDraft();

        // Latest entries
        $latestExercises = $exerciseRepo->findLatest(5);
        $latestPrograms  = $programRepo->findLatest(5);

        return $this->render('admin/dashboard.html.twig', [
            'categoryStats'   => $categoryStats,
            'totalExercises'  => $totalExercises,
            'totalPrograms'   => $totalPrograms,
            'publishedCount'  => $publishedCount,
            'draftCount'      => $draftCount,
            'latestExercises' => $latestExercises,
            'latestPrograms'  => $latestPrograms,
        ]);
    }
}

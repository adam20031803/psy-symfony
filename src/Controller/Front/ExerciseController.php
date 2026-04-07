<?php

namespace App\Controller\Front;

use App\Repository\ExerciseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Exercise;

#[Route('/fitness', name: 'front_')]
class ExerciseController extends AbstractController
{
    // -------------------------------------------------------------------
    // HOME — GET /fitness
    // -------------------------------------------------------------------
    #[Route('', name: 'home', methods: ['GET'])]
    public function home(ExerciseRepository $repo): Response
    {
        $latestExercises = $repo->findLatest(6);

        return $this->render('front/home.html.twig', [
            'latestExercises' => $latestExercises,
        ]);
    }

    // -------------------------------------------------------------------
    // EXERCISE LIST — GET /fitness/exercises
    // Optional filter: ?category=Cardio
    // -------------------------------------------------------------------
    #[Route('/exercises', name: 'exercise_list', methods: ['GET'])]
    public function list(Request $request, ExerciseRepository $repo): Response
    {
        $category  = $request->query->get('category');
        $exercises = $category
            ? $repo->findByCategory($category)
            : $repo->findAll();

        $categories = ['Cardio', 'Musculation', 'Yoga', 'HIIT', 'Flexibilité'];

        return $this->render('front/exercise/list.html.twig', [
            'exercises'        => $exercises,
            'categories'       => $categories,
            'selectedCategory' => $category,
        ]);
    }

    // -------------------------------------------------------------------
    // EXERCISE SHOW — GET /fitness/exercises/{id}
    // -------------------------------------------------------------------
    #[Route('/exercises/{id}', name: 'exercise_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Exercise $exercise): Response
    {
        return $this->render('front/exercise/show.html.twig', [
            'exercise' => $exercise,
        ]);
    }

    // -------------------------------------------------------------------
    // SEARCH — GET /fitness/exercises/search?q=squat
    // -------------------------------------------------------------------
    #[Route('/exercises/search', name: 'exercise_search', methods: ['GET'])]
    public function search(Request $request, ExerciseRepository $repo): Response
    {
        $query   = $request->query->get('q', '');
        $results = $query ? $repo->searchByQuery($query) : [];

        return $this->render('front/exercise/search.html.twig', [
            'query'   => $query,
            'results' => $results,
        ]);
    }
}

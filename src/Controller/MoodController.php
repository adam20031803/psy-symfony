<?php

namespace App\Controller;

use App\Entity\Mood;
use App\Form\MoodDashboardType;
use App\Repository\MoodRepository;
use App\Service\PersistenceService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moods')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class MoodController extends AbstractController
{
    public function __construct(
        private readonly MoodRepository $moodRepository,
        private readonly PersistenceService $persistence,
    ) {
    }

    #[Route(name: 'app_mood_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $mood = new Mood();

        if ($request->isMethod('POST')) {
            $editLp = $request->request->getInt('edit_lp');
            if ($editLp > 0) {
                $loaded = $this->moodRepository->findOneByOrderedListOffset($editLp - 1);
                if ($loaded instanceof Mood) {
                    $mood = $loaded;
                }
            } else {
                $editId = $request->request->getInt('edit_id');
                if ($editId > 0) {
                    $found = $this->moodRepository->find($editId);
                    if ($found instanceof Mood) {
                        $mood = $found;
                    }
                }
            }
        } else {
            $lp = $request->query->getInt('lp');
            $editId = $request->query->getInt('edit');
            if ($lp > 0) {
                $loaded = $this->moodRepository->findOneByOrderedListOffset($lp - 1);
                if (!$loaded instanceof Mood) {
                    throw $this->createNotFoundException();
                }
                $mood = $loaded;
            } elseif ($editId > 0) {
                $found = $this->moodRepository->find($editId);
                if (!$found instanceof Mood) {
                    throw $this->createNotFoundException();
                }
                $mood = $found;
            }
        }

        $form = $this->createForm(MoodDashboardType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$mood->getId()) {
                if (null === $mood->getCreatedAt()) {
                    $mood->setCreatedAt(new \DateTimeImmutable());
                }
                $this->persistence->save($mood);
                $this->addFlash('success', 'Humeur ajoutée.');
            } else {
                $this->persistence->flush();
                $this->addFlash('success', 'Humeur mise à jour.');
            }

            return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
        }

        $editLp = $request->isMethod('POST')
            ? $request->request->getInt('edit_lp')
            : $request->query->getInt('lp');

        $response = $this->render('dashboard/moods.html.twig', [
            'moods' => $this->moodRepository->findAllOrderedByName(),
            'form' => $form,
            'active_section' => 'moods',
            'hide_mental_subnav' => true,
            'editing' => null !== $mood->getId(),
            'edit_id' => $mood->getId(),
            'edit_lp' => $editLp,
        ]);
        $response->headers->set('Cache-Control', 'private, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    #[Route('/line-delete', name: 'app_mood_line_delete', methods: ['POST'])]
    public function deleteByLine(Request $request): Response
    {
        $lp = $request->request->getInt('lp');
        $token = $request->request->getString('_token');
        if ($lp < 1 || !$this->isCsrfTokenValid('delete_mood_line_'.$lp, $token)) {
            return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
        }

        $mood = $this->moodRepository->findOneByOrderedListOffset($lp - 1);
        if (!$mood instanceof Mood) {
            return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
        }

        try {
            $this->persistence->remove($mood);
            $this->addFlash('success', 'Humeur supprimée.');
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('danger', 'Suppression impossible : des entrées ou conseils sont encore liés à cette humeur.');
        }

        return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_mood_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Mood $mood): Response
    {
        if ($this->isCsrfTokenValid('delete'.$mood->getId(), $request->request->getString('_token'))) {
            try {
                $this->persistence->remove($mood);
                $this->addFlash('success', 'Humeur supprimée.');
            } catch (ForeignKeyConstraintViolationException) {
                $this->addFlash('danger', 'Suppression impossible : des entrées ou conseils sont encore liés à cette humeur.');
            }
        }

        return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
    }
}

<?php

namespace App\Controller;

use App\Entity\MentalTip;
use App\Form\MentalTipType;
use App\Repository\MentalTipRepository;
use App\Repository\MoodRepository;
use App\Service\PersistenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mental-tips')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class MentalTipController extends AbstractController
{
    public function __construct(
        private readonly MentalTipRepository $mentalTipRepository,
        private readonly MoodRepository $moodRepository,
        private readonly PersistenceService $persistence,
    ) {
    }

    #[Route(name: 'app_mental_tip_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $tip = new MentalTip();

        if ($request->isMethod('POST')) {
            $editLp = $request->request->getInt('edit_lp');
            if ($editLp > 0) {
                $loaded = $this->mentalTipRepository->findOneByOrderedListOffset($editLp - 1);
                if ($loaded instanceof MentalTip) {
                    $tip = $loaded;
                }
            } else {
                $editId = $request->request->getInt('edit_id');
                if ($editId > 0) {
                    $found = $this->mentalTipRepository->find($editId);
                    if ($found instanceof MentalTip) {
                        $tip = $found;
                    }
                }
            }
        } else {
            $lp = $request->query->getInt('lp');
            $editId = $request->query->getInt('edit');
            if ($lp > 0) {
                $loaded = $this->mentalTipRepository->findOneByOrderedListOffset($lp - 1);
                if (!$loaded instanceof MentalTip) {
                    throw $this->createNotFoundException();
                }
                $tip = $loaded;
            } elseif ($editId > 0) {
                $found = $this->mentalTipRepository->find($editId);
                if (!$found instanceof MentalTip) {
                    throw $this->createNotFoundException();
                }
                $tip = $found;
            }
        }

        $form = $this->createForm(MentalTipType::class, $tip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->ensureTipReferencesExistingMood($tip);

            if (!$tip->getId()) {
                $this->persistence->save($tip);
                $this->addFlash('success', 'Conseil enregistré.');
            } else {
                $this->persistence->flush();
                $this->addFlash('success', 'Conseil mis à jour.');
            }

            return $this->redirectToRoute('app_mental_tip_index', [], Response::HTTP_SEE_OTHER);
        }

        $editLp = $request->isMethod('POST')
            ? $request->request->getInt('edit_lp')
            : $request->query->getInt('lp');

        $response = $this->render('dashboard/mental_tips.html.twig', [
            'tips' => $this->mentalTipRepository->findForDashboardList(),
            'form' => $form,
            'active_section' => 'tips',
            'editing' => null !== $tip->getId(),
            'edit_id' => $tip->getId(),
            'edit_lp' => $editLp,
        ]);
        $response->headers->set('Cache-Control', 'private, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    #[Route('/line-delete', name: 'app_mental_tip_line_delete', methods: ['POST'])]
    public function deleteByLine(Request $request): Response
    {
        $lp = $request->request->getInt('lp');
        $token = $request->request->getString('_token');
        if ($lp < 1 || !$this->isCsrfTokenValid('delete_mental_tip_line_'.$lp, $token)) {
            return $this->redirectToRoute('app_mental_tip_index', [], Response::HTTP_SEE_OTHER);
        }

        $tip = $this->mentalTipRepository->findOneByOrderedListOffset($lp - 1);
        if ($tip instanceof MentalTip) {
            $this->persistence->remove($tip);
            $this->addFlash('success', 'Conseil supprimé.');
        }

        return $this->redirectToRoute('app_mental_tip_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_mental_tip_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, MentalTip $tip): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tip->getId(), $request->request->getString('_token'))) {
            $this->persistence->remove($tip);
            $this->addFlash('success', 'Conseil supprimé.');
        }

        return $this->redirectToRoute('app_mental_tip_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Évite mood_id = 0 ou inconnu en SQL : sinon Doctrine enregistre mais la ligne est incohérente avec mood.
     */
    private function ensureTipReferencesExistingMood(MentalTip $tip): void
    {
        $mood = $tip->getMood();
        $fallback = $this->moodRepository->findOneBy([], ['id' => 'ASC']);
        if (!$fallback) {
            return;
        }

        if ($mood === null) {
            $tip->setMood($fallback);

            return;
        }

        $id = $mood->getId();
        if ($id === null || $id < 1) {
            $tip->setMood($fallback);

            return;
        }

        $fresh = $this->moodRepository->find($id);
        if ($fresh === null) {
            $tip->setMood($fallback);
        }
    }
}

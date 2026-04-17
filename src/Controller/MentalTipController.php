<?php

namespace App\Controller;

use App\Entity\MentalTip;
use App\Form\MentalTipType;
use App\Repository\MentalTipRepository;
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
        private readonly PersistenceService $persistence,
    ) {
    }

    #[Route(name: 'app_mental_tip_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $editId = $request->isMethod('POST')
            ? $request->request->getInt('edit_id')
            : $request->query->getInt('edit');

        $tip = new MentalTip();
        if ($editId > 0) {
            $found = $this->mentalTipRepository->find($editId);
            if (!$found) {
                throw $this->createNotFoundException();
            }
            $tip = $found;
        }

        $form = $this->createForm(MentalTipType::class, $tip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$tip->getId()) {
                $this->persistence->save($tip);
                $this->addFlash('success', 'Conseil enregistré.');
            } else {
                $this->persistence->flush();
                $this->addFlash('success', 'Conseil mis à jour.');
            }

            return $this->redirectToRoute('app_mental_tip_index');
        }

        return $this->render('dashboard/mental_tips.html.twig', [
            'tips' => $this->mentalTipRepository->findForDashboard(),
            'form' => $form,
            'active_section' => 'tips',
            'editing' => null !== $tip->getId(),
            'edit_id' => $tip->getId(),
        ]);
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
}

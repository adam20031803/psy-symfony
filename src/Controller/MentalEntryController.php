<?php

namespace App\Controller;

use App\Entity\MentalEntry;
use App\Form\MentalEntryType;
use App\Repository\MentalEntryRepository;
use App\Service\PersistenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mental-entries')]
final class MentalEntryController extends AbstractController
{
    public function __construct(
        private readonly MentalEntryRepository $mentalEntryRepository,
        private readonly PersistenceService $persistence,
    ) {
    }

    #[Route(name: 'app_mental_entry_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $editId = $request->isMethod('POST')
            ? $request->request->getInt('edit_id')
            : $request->query->getInt('edit');

        $entry = new MentalEntry();
        if ($editId <= 0) {
            $entry->setEntryDate(new \DateTimeImmutable('today'));
            $entry->setEmotionLevel(5);
        }
        if ($editId > 0) {
            $found = $this->mentalEntryRepository->find($editId);
            if (!$found) {
                throw $this->createNotFoundException();
            }
            $entry = $found;
        }

        $form = $this->createForm(MentalEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$entry->getId()) {
                $this->persistence->save($entry);
                $this->addFlash('success', 'Entrée enregistrée.');
            } else {
                $this->persistence->flush();
                $this->addFlash('success', 'Entrée mise à jour.');
            }

            return $this->redirectToRoute('app_mental_entry_index');
        }

        return $this->render('dashboard/mental_entries.html.twig', [
            'entries' => $this->mentalEntryRepository->findForDashboard(),
            'form' => $form,
            'active_section' => 'entries',
            'editing' => null !== $entry->getId(),
            'edit_id' => $entry->getId(),
        ]);
    }

    #[Route('/{id}', name: 'app_mental_entry_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, MentalEntry $entry): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entry->getId(), $request->request->getString('_token'))) {
            $this->persistence->remove($entry);
            $this->addFlash('success', 'Entrée supprimée.');
        }

        return $this->redirectToRoute('app_mental_entry_index', [], Response::HTTP_SEE_OTHER);
    }
}

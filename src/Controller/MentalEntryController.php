<?php

namespace App\Controller;

use App\Entity\MentalEntry;
use App\Form\MentalEntryType;
use App\Repository\MentalEntryRepository;
use App\Service\PdfRenderService;
use App\Service\PersistenceService;
use App\Service\TwilioSmsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mental-entries')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class MentalEntryController extends AbstractController
{
    public function __construct(
        private readonly MentalEntryRepository $mentalEntryRepository,
        private readonly PersistenceService $persistence,
        private readonly TwilioSmsService $twilioSmsService,
        private readonly PdfRenderService $pdfRenderService,
    ) {
    }

    #[Route('/export/pdf', name: 'app_mental_entry_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request): Response
    {
        $entries = $this->mentalEntryRepository->findForDashboard();
        $highlight = null;
        $id = $request->query->getInt('entry');
        if ($id > 0) {
            $candidate = $this->mentalEntryRepository->find($id);
            if ($candidate instanceof MentalEntry) {
                $highlight = $candidate;
            }
        }
        $generated = new \DateTimeImmutable();

        return $this->pdfRenderService->renderPdf('pdf/mental_entries.html.twig', [
            'entries' => $entries,
            'highlight_entry' => $highlight,
            'generated_at' => $generated,
        ], 'mental-entries-'.$generated->format('Y-m-d').'.pdf');
    }

    #[Route(name: 'app_mental_entry_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $entry = new MentalEntry();
        $entry->setEntryDate(new \DateTimeImmutable('today'));
        $entry->setEmotionLevel(5);

        if ($request->isMethod('POST')) {
            $editLp = $request->request->getInt('edit_lp');
            if ($editLp > 0) {
                $loaded = $this->mentalEntryRepository->findOneByOrderedListOffset($editLp - 1);
                if ($loaded instanceof MentalEntry) {
                    $entry = $loaded;
                }
            } elseif ($request->request->getInt('edit_id') > 0) {
                $editId = $request->request->getInt('edit_id');
                $found = $this->mentalEntryRepository->find($editId);
                if ($found instanceof MentalEntry) {
                    $entry = $found;
                }
            }
        } else {
            $lp = $request->query->getInt('lp');
            $editId = $request->query->getInt('edit');
            if ($lp > 0) {
                $loaded = $this->mentalEntryRepository->findOneByOrderedListOffset($lp - 1);
                if (!$loaded instanceof MentalEntry) {
                    throw $this->createNotFoundException();
                }
                $entry = $loaded;
            } elseif ($editId > 0) {
                $found = $this->mentalEntryRepository->find($editId);
                if (!$found instanceof MentalEntry) {
                    throw $this->createNotFoundException();
                }
                $entry = $found;
            }
        }

        $form = $this->createForm(MentalEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$entry->getId()) {
                $this->persistence->save($entry);
                $this->notifyByEmotionLevel($entry);
                $this->addFlash('success', 'Entrée enregistrée.');
            } else {
                $this->persistence->flush();
                $this->notifyByEmotionLevel($entry);
                $this->addFlash('success', 'Entrée mise à jour.');
            }

            return $this->redirectToRoute('app_mental_entry_index', [], Response::HTTP_SEE_OTHER);
        }

        $editLp = $request->isMethod('POST')
            ? $request->request->getInt('edit_lp')
            : $request->query->getInt('lp');

        $response = $this->render('dashboard/mental_entries.html.twig', [
            'entries' => $this->mentalEntryRepository->findForDashboard(),
            'form' => $form,
            'active_section' => 'entries',
            'editing' => null !== $entry->getId(),
            'edit_id' => $entry->getId(),
            'edit_lp' => $editLp,
        ]);
        $response->headers->set('Cache-Control', 'private, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    #[Route('/line-delete', name: 'app_mental_entry_line_delete', methods: ['POST'])]
    public function deleteEntryByLine(Request $request): Response
    {
        $lp = $request->request->getInt('lp');
        $token = $request->request->getString('_token');
        if ($lp < 1 || !$this->isCsrfTokenValid('delete_mental_entry_line_'.$lp, $token)) {
            return $this->redirectToRoute('app_mental_entry_index', [], Response::HTTP_SEE_OTHER);
        }

        $entry = $this->mentalEntryRepository->findOneByOrderedListOffset($lp - 1);
        if ($entry instanceof MentalEntry) {
            $this->persistence->remove($entry);
            $this->addFlash('success', 'Entrée supprimée.');
        }

        return $this->redirectToRoute('app_mental_entry_index', [], Response::HTTP_SEE_OTHER);
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

    private function notifyByEmotionLevel(MentalEntry $entry): void
    {
        $level = $entry->getEmotionLevel();
        if (null === $level || ($level > 3 && $level < 9)) {
            return;
        }

        $severityLabel = $level <= 3 ? 'ALERTE BASSE' : 'ALERTE HAUTE';
        $moodName = $entry->getMood()?->getMoodName() ?? 'Non défini';
        $activity = $entry->getActivity() ?? 'Non définie';
        $entryDate = $entry->getEntryDate()?->format('d/m/Y') ?? 'Date inconnue';

        $smsBody = sprintf(
            '[PsyApp] %s - Niveau emotion: %d/10 | Mood: %s | Activite: %s | Date: %s',
            $severityLabel,
            $level,
            $moodName,
            $activity,
            $entryDate
        );

        $result = $this->twilioSmsService->send($smsBody);
        if ($result['sent']) {
            $this->addFlash('success', 'Alerte SMS envoyée (Twilio).');
        } else {
            // Entrée déjà enregistrée : échec SMS = avertissement, pas une erreur bloquante
            $this->addFlash('warning', 'Alerte SMS non envoyée : '.$result['message']);
        }
    }
}

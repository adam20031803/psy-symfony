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

#[Route('/moods')]
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
        $editId = $request->isMethod('POST')
            ? $request->request->getInt('edit_id')
            : $request->query->getInt('edit');

        $mood = new Mood();
        if ($editId > 0) {
            $found = $this->moodRepository->find($editId);
            if (!$found) {
                throw $this->createNotFoundException();
            }
            $mood = $found;
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

            return $this->redirectToRoute('app_mood_index');
        }

        return $this->render('dashboard/moods.html.twig', [
            'moods' => $this->moodRepository->findAllOrderedByName(),
            'form' => $form,
            'active_section' => 'moods',
            'editing' => null !== $mood->getId(),
            'edit_id' => $mood->getId(),
        ]);
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

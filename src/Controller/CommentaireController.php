<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Post;
use App\Service\AiModerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comments', name: 'comment_')]
class CommentaireController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AiModerator $moderator
    ) {}

    #[Route('/post/{id}/new', name: 'create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Post $post, Request $request): JsonResponse
    {
        $contenu = $request->request->get('contenu');
        // Handle Symfony Form data structure
        if (!$contenu && $request->request->has('commentaire')) {
            $formData = $request->request->all('commentaire');
            $contenu = $formData['contenu'] ?? null;
        }

        if (empty($contenu)) {
            return new JsonResponse(['success' => false, 'error' => 'Le commentaire ne peut pas être vide.'], 400);
        }

        $commentaire = new Commentaire();
        $commentaire->setPost($post);
        $commentaire->setUser($this->getUser());
        $commentaire->setContenu($contenu);
        $commentaire->setCreatedAt(new \DateTime());

        $analysis = $this->moderator->checkContent($contenu);
        if ($analysis['valid'] === false) {
            return new JsonResponse(['success' => false, 'error' => $analysis['reason']], 400);
        }

        $this->em->persist($commentaire);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'id' => $commentaire->getId(),
            'contenu' => $commentaire->getContenu(),
            'author' => $this->getUser()->getUserIdentifier(),
            'createdAt' => $commentaire->getCreatedAt()->format('d/m/Y H:i'),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Commentaire $commentaire, Request $request): JsonResponse
    {
        if ($commentaire->getUser() !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Action non autorisée.'], 403);
        }

        $contenu = $request->request->get('contenu');
        if (empty($contenu)) {
            return new JsonResponse(['success' => false, 'error' => 'Le commentaire ne peut pas être vide.'], 400);
        }

        $analysis = $this->moderator->checkContent($contenu);
        if ($analysis['valid'] === false) {
            return new JsonResponse(['success' => false, 'error' => $analysis['reason']], 400);
        }

        $commentaire->setContenu($contenu);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'contenu' => $commentaire->getContenu()
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(Commentaire $commentaire, Request $request): JsonResponse
    {
        if ($commentaire->getUser() !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Action non autorisée.'], 403);
        }

        if (!$this->isCsrfTokenValid('delete_comment' . $commentaire->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'error' => 'Token CSRF invalide.'], 400);
        }

        $this->em->remove($commentaire);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }
}

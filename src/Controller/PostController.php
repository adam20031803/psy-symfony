<?php

// src/Controller/PostController.php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\PostShare;
use App\Form\CommentaireType;
use App\Form\PostType;
use App\Repository\CategorieRepository;
use App\Repository\CommentaireRepository;
use App\Repository\PostLikeRepository;
use App\Repository\PostRepository;
use App\Repository\PostShareRepository;
use App\Service\BadWordChecker;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/posts', name: 'post_')]
class PostController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PostRepository         $postRepository,
        private readonly BadWordChecker         $badWordChecker,
        private readonly string                 $postsDirectory
    ) {}

    /* ════════════════════════════════════════════════════════════════
       INDEX — liste des posts avec filtres
    ════════════════════════════════════════════════════════════════ */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, CategorieRepository $categorieRepository, PaginatorInterface $paginator): Response
    {
        $search      = $request->query->get('q');
        $categorieId = $request->query->get('categorie') ? (int) $request->query->get('categorie') : null;

        $qb         = $this->postRepository->getQueryBuilderForFilters($search, $categorieId);
        $categories = $categorieRepository->findAll();

        $posts = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            6 // 6 posts par page
        );

        return $this->render('post/index.html.twig', [
            'posts'             => $posts,
            'categories'        => $categories,
            'search'            => $search,
            'selectedCategorie' => $categorieId,
        ]);
    }

    /* ════════════════════════════════════════════════════════════════
       NEW — formulaire de création
    ════════════════════════════════════════════════════════════════ */
    #[Route('/new', name: 'new', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(): Response
    {
        $form = $this->createForm(PostType::class, new Post());
        return $this->render('post/new.html.twig', ['form' => $form->createView()]);
    }

    /* ════════════════════════════════════════════════════════════════
       CREATE — traitement de la création
    ════════════════════════════════════════════════════════════════ */
    #[Route('/new', name: 'create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form->get('imageFile')->getData(), $post, $slugger);

            $post->setUser($this->getUser());
            $post->setCreatedAt(new \DateTime());

            $this->em->persist($post);
            $this->em->flush();

            $this->addFlash('success', 'Post publié avec succès !');
            return $this->redirectToRoute('post_index');
        }

        return $this->render('post/new.html.twig', ['form' => $form->createView()]);
    }

    /* ════════════════════════════════════════════════════════════════
       SHOW — détail d'un post + commentaires
    ════════════════════════════════════════════════════════════════ */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Post $post, PostLikeRepository $likeRepo, PostShareRepository $shareRepo): Response
    {
        $commentaireForm = $this->createForm(CommentaireType::class, new Commentaire());

        // Compter les likes/dislikes/reposts pour ce post
        $likesCount    = $likeRepo->countLikes($post);
        $dislikesCount = $likeRepo->countDislikes($post);
        $sharesCount   = $post->countShares();

        // Vote actuel de l'utilisateur connecté (null, 'like' ou 'dislike')
        $userVote = null;
        $hasShared = false;
        if ($this->getUser()) {
            $existingLike = $likeRepo->findUserLike($post, $this->getUser());
            $userVote = $existingLike ? $existingLike->getType() : null;
            $hasShared = $post->hasUserShared($this->getUser());
        }

        return $this->render('post/show.html.twig', [
            'post'            => $post,
            'commentaireForm' => $commentaireForm->createView(),
            'likesCount'      => $likesCount,
            'dislikesCount'   => $dislikesCount,
            'sharesCount'     => $sharesCount,
            'userVote'        => $userVote,
            'hasShared'       => $hasShared,
        ]);
    }

    /* ════════════════════════════════════════════════════════════════
       EDIT / UPDATE
    ════════════════════════════════════════════════════════════════ */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Post $post): Response
    {
        $this->assertCanEdit($post);
        $form = $this->createForm(PostType::class, $post);
        return $this->render('post/edit.html.twig', ['form' => $form->createView(), 'post' => $post]);
    }

    #[Route('/{id}/edit', name: 'update', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update(Post $post, Request $request, SluggerInterface $slugger): Response
    {
        $this->assertCanEdit($post);
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form->get('imageFile')->getData(), $post, $slugger);
            $post->setUpdatedAt(new \DateTime());
            $this->em->flush();
            $this->addFlash('success', 'Post modifié avec succès !');
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/edit.html.twig', ['form' => $form->createView(), 'post' => $post]);
    }

    /* ════════════════════════════════════════════════════════════════
       DELETE
    ════════════════════════════════════════════════════════════════ */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(Post $post, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('post_index');
        }
        $this->assertCanEdit($post);

        $this->em->remove($post);
        $this->em->flush();

        $this->addFlash('success', 'Post supprimé.');
        return $this->redirectToRoute('post_index');
    }

    /* ════════════════════════════════════════════════════════════════
       ADD COMMENT
    ════════════════════════════════════════════════════════════════ */
    #[Route('/{id}/comment', name: 'add_comment', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addComment(Post $post, Request $request): Response
    {
        $commentaire = new Commentaire();
        $form        = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ── BadWord check (IA Gemini) ──
            if ($this->badWordChecker->containsBadWord($commentaire->getContenu())) {
                $this->addFlash('error', '🚫 Votre commentaire contient des mots interdits et n\'a pas été publié.');
                return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
            }

            $commentaire->setPost($post);
            $commentaire->setUser($this->getUser());
            $commentaire->setCreatedAt(new \DateTime());
            $this->em->persist($commentaire);
            $this->em->flush();
            $this->addFlash('success', '💬 Commentaire publié.');
        }

        return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
    }

    /* ════════════════════════════════════════════════════════════════
       EDIT COMMENT
    ════════════════════════════════════════════════════════════════ */
    #[Route('/{postId}/comment/{id}/edit', name: 'edit_comment', methods: ['POST'], requirements: ['postId' => '\d+', 'id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function editComment(int $postId, Commentaire $commentaire, Request $request, CommentaireRepository $commentaireRepo): Response
    {
        if ($commentaire->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('Action non autorisée.');
        }

        $newContent = trim($request->request->get('contenu', ''));

        if (empty($newContent)) {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('post_show', ['id' => $postId]);
        }

        if ($this->badWordChecker->containsBadWord($newContent)) {
            $this->addFlash('error', '🚫 Votre commentaire contient des mots interdits.');
            return $this->redirectToRoute('post_show', ['id' => $postId]);
        }

        $commentaire->setContenu($newContent);
        $this->em->flush();
        $this->addFlash('success', '✏️ Commentaire modifié.');

        return $this->redirectToRoute('post_show', ['id' => $postId]);
    }

    /* ════════════════════════════════════════════════════════════════
       DELETE COMMENT
    ════════════════════════════════════════════════════════════════ */
    #[Route('/{postId}/comment/{id}/delete', name: 'delete_comment', methods: ['POST'], requirements: ['postId' => '\d+', 'id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteComment(int $postId, Commentaire $commentaire, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_comment_' . $commentaire->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('post_show', ['id' => $postId]);
        }

        if ($commentaire->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('Action non autorisée.');
        }

        $this->em->remove($commentaire);
        $this->em->flush();
        $this->addFlash('success', '🗑️ Commentaire supprimé.');

        return $this->redirectToRoute('post_show', ['id' => $postId]);
    }

    /* ════════════════════════════════════════════════════════════════
       LIKE / DISLIKE (toggle via AJAX ou form POST)
    ════════════════════════════════════════════════════════════════ */
    #[Route('/{id}/like', name: 'like', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function like(Post $post, Request $request, PostLikeRepository $likeRepo): JsonResponse
    {
        $type = $request->request->get('type', PostLike::TYPE_LIKE);
        if (!in_array($type, [PostLike::TYPE_LIKE, PostLike::TYPE_DISLIKE], true)) {
            return new JsonResponse(['error' => 'Type invalide.'], 400);
        }

        $user        = $this->getUser();
        $existingVote = $likeRepo->findUserLike($post, $user);

        if ($existingVote) {
            if ($existingVote->getType() === $type) {
                // Même vote → annuler (toggle off)
                $this->em->remove($existingVote);
                $action = 'removed';
            } else {
                // Vote différent → changer
                $existingVote->setType($type);
                $action = 'changed';
            }
        } else {
            // Nouveau vote
            $like = (new PostLike())
                ->setPost($post)
                ->setUser($user)
                ->setType($type)
                ->setCreatedAt(new \DateTime());
            $this->em->persist($like);
            $action = 'added';
        }

        $this->em->flush();

        return new JsonResponse([
            'action'   => $action,
            'likes'    => $likeRepo->countLikes($post),
            'dislikes' => $likeRepo->countDislikes($post),
            'userVote' => $action === 'removed' ? null : $type,
        ]);
    }

    /* ════════════════════════════════════════════════════════════════
       REPOST (partager le post dans sa timeline)
    ════════════════════════════════════════════════════════════════ */
    #[Route('/{id}/repost', name: 'repost', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function repost(Post $post, PostShareRepository $shareRepo): JsonResponse
    {
        $user = $this->getUser();

        // Un seul repost par utilisateur par post
        if ($post->hasUserShared($user)) {
            return new JsonResponse([
                'message' => 'Vous avez déjà reposté ce post.',
                'shares'  => $post->countShares(),
                'shared'  => true,
            ]);
        }

        $share = (new PostShare())
            ->setPost($post)
            ->setUser($user);
        $this->em->persist($share);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Post reposté avec succès !',
            'shares'  => $post->countShares(),
            'shared'  => true,
        ]);
    }

    /* ════════════════════════════════════════════════════════════════
       Helpers privés
    ════════════════════════════════════════════════════════════════ */

    private function assertCanEdit(Post $post): void
    {
        if ($this->getUser() !== $post->getUser()) {
            throw new AccessDeniedHttpException('Action non autorisée.');
        }
    }

    private function handleImageUpload(mixed $imageFile, Post $post, SluggerInterface $slugger): void
    {
        if (!$imageFile) {
            return;
        }
        $safe        = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
        $newFilename = $safe . '-' . uniqid() . '.' . $imageFile->guessExtension();
        try {
            $imageFile->move($this->postsDirectory, $newFilename);
            $post->setImage($newFilename);
        } catch (FileException) {
            // On garde l'ancienne image en cas d'erreur
        }
    }
}

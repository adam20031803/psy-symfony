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
use App\Repository\PostLikeRepository;
use App\Repository\PostRepository;
use App\Repository\PostShareRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly PostRepository         $postRepository
    ) {}

    /* ════════════════════════════════════════════════════════════════
       INDEX — liste des posts avec filtres
    ════════════════════════════════════════════════════════════════ */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, CategorieRepository $categorieRepository): Response
    {
        $search      = $request->query->get('q');
        $categorieId = $request->query->get('categorie') ? (int) $request->query->get('categorie') : null;

        $queryBuilder = $this->postRepository->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC');

        if ($search) {
            $queryBuilder->andWhere('p.titre LIKE :search OR p.contenu LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($categorieId) {
            $queryBuilder->andWhere('p.categorie = :cat')
                ->setParameter('cat', $categorieId);
        }

        // Manual Pagination logic
        $page = $request->query->getInt('page', 1);
        $limit = 6;
        $offset = ($page - 1) * $limit;

        $totalPosts = count($queryBuilder->getQuery()->getResult());
        $totalPages = ceil($totalPosts / $limit);

        $posts = $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('post/index.html.twig', [
            'posts'             => $posts,
            'currentPage'       => $page,
            'totalPages'        => $totalPages,
            'categories'        => $categorieRepository->findAll(),
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
    public function create(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($this->getUser());
            $post->setCreatedAt(new \DateTime());

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/posts',
                        $newFilename
                    );
                    $post->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

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
    public function update(Post $post, Request $request): Response
    {
        $this->assertCanEdit($post);
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Delete old image if exists
                if ($post->getImage()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/posts/' . $post->getImage();
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/posts',
                        $newFilename
                    );
                    $post->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

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

        // Delete image associated with post
        if ($post->getImage()) {
            $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/posts/' . $post->getImage();
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

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
            $commentaire->setPost($post);
            $commentaire->setUser($this->getUser());
            $commentaire->setCreatedAt(new \DateTime());
            $this->em->persist($commentaire);
            $this->em->flush();
            $this->addFlash('success', 'Commentaire ajouté.');
        }

        return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
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
}

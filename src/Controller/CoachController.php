<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/coach')]
class CoachController extends AbstractController
{
    #[Route('/', name: 'app_coach_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $keyword = $request->query->get('search', '');
        $sort    = $request->query->get('sort', 'nom_asc');
        $statut  = $request->query->get('statut', ''); // '1' pour actif, '0' pour inactif
        
        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', 'coach');

        if ($keyword) {
            $qb->andWhere('u.nom LIKE :kw OR u.prenom LIKE :kw OR u.email LIKE :kw')
               ->setParameter('kw', '%' . $keyword . '%');
        }

        if ($statut !== '') {
            $qb->andWhere('u.isActive = :isActive')
               ->setParameter('isActive', $statut === '1');
        }

        if ($sort === 'nom_desc') {
            $qb->orderBy('u.nom', 'DESC');
        } else {
            $qb->orderBy('u.nom', 'ASC');
        }

        $coaches = $qb->getQuery()->getResult();

        return $this->render('coach/index.html.twig', [
            'coaches' => $coaches,
            'filters' => [
                'search' => $keyword,
                'sort' => $sort,
                'statut' => $statut
            ],
            'stats' => [
                'total' => count($coaches),
                'actifs' => count(array_filter($coaches, fn($c) => $c->isActive())),
            ]
        ]);
    }

    #[Route('/new', name: 'app_coach_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $user = new User();
        $user->setRole('coach');
        $user->setNom(trim($request->request->get('nom', '')));
        $user->setPrenom(trim($request->request->get('prenom', '')));
        $user->setEmail(trim($request->request->get('email', '')));
        $user->setPassword('coach_' . uniqid());
        
        $age = $request->request->get('age');
        if ($age !== null && $age !== '') $user->setAge((int)$age);
        
        $telephone = $request->request->get('telephone');
        if ($telephone !== null && $telephone !== '') $user->setTelephone($telephone);
        
        $user->setActive($request->request->get('actif') !== null);

        // Handle CV Upload
        $cvFile = $request->files->get('cv');
        if ($cvFile) {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
            if (!file_exists($uploadsDir)) mkdir($uploadsDir, 0777, true);

            $newFilename = uniqid() . '.' . $cvFile->guessExtension();
            try {
                $cvFile->move($uploadsDir, $newFilename);
                $user->setCv('/uploads/cv/' . $newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', "Erreur lors de l'import du CV.");
            }
        }

        // Handle Photo Upload
        $photoFile = $request->files->get('photo');
        if ($photoFile) {
            $uploadsDirPhoto = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
            if (!file_exists($uploadsDirPhoto)) mkdir($uploadsDirPhoto, 0777, true);

            $newPhotoFilename = uniqid() . '.' . $photoFile->guessExtension();
            try {
                $photoFile->move($uploadsDirPhoto, $newPhotoFilename);
                $user->setPhoto('/uploads/photos/' . $newPhotoFilename);
            } catch (FileException $e) {
                $this->addFlash('error', "Erreur lors de l'import de la photo.");
            }
        }

        // ── Server-side validation ──
        $errors = $validator->validate($user, null, ['Default', 'coach']);
        
        // Manual check for email uniqueness
        $existingEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        if ($existingEmail) {
            $this->addFlash('error', "Un utilisateur avec cet email existe déjà.");
            return $this->redirectToRoute('app_coach_index');
        }

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash('error', '❌ ' . implode(' | ', $errorMessages));
            return $this->redirectToRoute('app_coach_index');
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', '👨‍🏫 Coach ajouté avec succès!');
        return $this->redirectToRoute('app_coach_index');
    }

    #[Route('/{id}/edit', name: 'app_coach_edit', methods: ['POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($user->getRole() !== 'coach') {
            throw $this->createAccessDeniedException();
        }

        $user->setNom(trim($request->request->get('nom', $user->getNom())));
        $user->setPrenom(trim($request->request->get('prenom', $user->getPrenom())));
        $user->setEmail(trim($request->request->get('email', $user->getEmail())));
        
        $age = $request->request->get('age');
        if ($age !== null && $age !== '') $user->setAge((int)$age);
        
        $telephone = $request->request->get('telephone');
        if ($telephone !== null && $telephone !== '') $user->setTelephone($telephone);
        
        $user->setActive($request->request->get('actif') !== null);

        // Handle CV Upload
        $cvFile = $request->files->get('cv');
        if ($cvFile) {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
            if (!file_exists($uploadsDir)) mkdir($uploadsDir, 0777, true);

            $newFilename = uniqid() . '.' . $cvFile->guessExtension();
            try {
                $cvFile->move($uploadsDir, $newFilename);
                $user->setCv('/uploads/cv/' . $newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', "Erreur lors de l'import du CV.");
            }
        }

        // Handle Photo Upload
        $photoFile = $request->files->get('photo');
        if ($photoFile) {
            $uploadsDirPhoto = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
            if (!file_exists($uploadsDirPhoto)) mkdir($uploadsDirPhoto, 0777, true);

            $newPhotoFilename = uniqid() . '.' . $photoFile->guessExtension();
            try {
                $photoFile->move($uploadsDirPhoto, $newPhotoFilename);
                $user->setPhoto('/uploads/photos/' . $newPhotoFilename);
            } catch (FileException $e) {
                $this->addFlash('error', "Erreur lors de l'import de la photo.");
            }
        }

        // ── Server-side validation ──
        $errors = $validator->validate($user, null, ['Default', 'coach']);
        
        // Manual check for email uniqueness
        $existingEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        if ($existingEmail && $existingEmail->getId() !== $user->getId()) {
            $this->addFlash('error', "Un utilisateur avec cet email existe déjà.");
            return $this->redirectToRoute('app_coach_index');
        }

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->addFlash('error', '❌ ' . implode(' | ', $errorMessages));
            return $this->redirectToRoute('app_coach_index');
        }

        $entityManager->flush();

        $this->addFlash('success', '✏️ Coach modifié avec succès!');
        return $this->redirectToRoute('app_coach_index');
    }

    #[Route('/{id}/delete', name: 'app_coach_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($user->getRole() !== 'coach') {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', '🗑️ Coach supprimé!');
        }

        return $this->redirectToRoute('app_coach_index');
    }
}

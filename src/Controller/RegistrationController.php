<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $error = null;
        $formData = [];

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();

            $prenom    = trim($formData['prenom'] ?? '');
            $age       = (int) ($formData['age'] ?? 0);
            $telephone = trim($formData['telephone'] ?? '');
            $email     = trim($formData['email'] ?? '');
            $password  = $formData['password'] ?? '';
            $confirm   = $formData['confirm_password'] ?? '';
            $role      = in_array($formData['role'] ?? '', ['user', 'coach'], true)
                         ? $formData['role']
                         : 'user';

            // Validation
            if (empty($prenom) || empty($email) || empty($password)) {
                $error = 'Tous les champs obligatoires doivent être remplis.';
            } elseif ($password !== $confirm) {
                $error = 'Les deux mots de passe ne correspondent pas.';
            } elseif (strlen($password) < 6) {
                $error = 'Le mot de passe doit contenir au moins 6 caractères.';
            } elseif ($entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
                $error = 'Un compte avec cet email existe déjà.';
            } else {
                $user = new User();
                $user->setPrenom($prenom);
                $user->setNom(''); // nom facultatif, vide par défaut
                $user->setAge($age > 0 ? $age : null);
                $user->setTelephone(!empty($telephone) ? $telephone : null);
                $user->setEmail($email);
                $user->setRole($role);
                $user->setPassword($passwordHasher->hashPassword($user, $password));

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'error'    => $error,
            'formData' => $formData,
        ]);
    }
}

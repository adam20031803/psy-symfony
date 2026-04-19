<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
            $nom       = trim($formData['nom'] ?? '');
            $age       = (int) ($formData['age'] ?? 0);
            $telephone = trim($formData['telephone'] ?? '');
            $email     = trim($formData['email'] ?? '');
            $password  = $formData['password'] ?? '';
            $confirm   = $formData['confirm_password'] ?? '';
            // New users are always created with 'user' role - only admins can be coaches/admins
            $role      = 'user';

            // Validation
            if (empty($prenom) || empty($nom) || empty($email) || empty($password)) {
                $error = 'Tous les champs obligatoires (Prénom, Nom, Email, Mot de passe) doivent être remplis.';
            } elseif ($password !== $confirm) {
                $error = 'Les deux mots de passe ne correspondent pas.';
            } elseif (strlen($password) < 6) {
                $error = 'Le mot de passe doit contenir au moins 6 caractères.';
            } elseif ($entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
                $error = 'Un compte avec cet email existe déjà.';
            }
            
            if ($error) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => false, 'error' => $error], 400);
                }
            } else {
                $user = new User();
                $user->setPrenom($prenom);
                $user->setNom($nom);
                $user->setAge($age > 0 ? $age : null);
                $user->setTelephone(!empty($telephone) ? $telephone : null);
                $user->setEmail($email);
                $user->setRole($role);
                $user->setPassword($passwordHasher->hashPassword($user, $password));

                // Optional Face ID descriptor submitted with the form
                $faceDescriptorRaw = trim($formData['face_descriptor'] ?? '');
                if (!empty($faceDescriptorRaw)) {
                    $descriptor = json_decode($faceDescriptorRaw, true);
                    if (is_array($descriptor) && count($descriptor) === 128) {
                        $user->setFaceDescriptor(array_map('floatval', $descriptor));
                    }
                }

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_login')]);
                }
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'error'    => $error,
            'formData' => $formData,
        ]);
    }
}

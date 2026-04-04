<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ResetPasswordController extends AbstractController
{
    private const TOKEN_TTL_HOURS = 1;

    #[Route(
        '/reset-password/{token}',
        name: 'app_reset_password',
        requirements: ['token' => '[a-f0-9]{64}'],
        methods: ['GET', 'POST']
    )]
    public function reset(
        string $token,
        Request $request,
        UserRepository $userRepo,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $user = $userRepo->findOneByPasswordResetToken($token);

        if (!$user || !$this->isTokenValid($user->getPasswordResetRequestedAt())) {
            return $this->render('security/reset_password_invalid.html.twig');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $password  = $request->request->get('password', '');
            $confirm   = $request->request->get('confirm_password', '');

            if (!\is_string($password) || $password === '') {
                $error = 'Veuillez saisir un mot de passe.';
            } elseif ($password !== $confirm) {
                $error = 'Les deux mots de passe ne correspondent pas.';
            } elseif (strlen($password) < 6) {
                $error = 'Le mot de passe doit contenir au moins 6 caractères.';
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setPasswordResetToken(null);
                $user->setPasswordResetRequestedAt(null);
                $em->flush();

                $this->addFlash('success', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'error' => $error,
        ]);
    }

    private function isTokenValid(?\DateTimeImmutable $requestedAt): bool
    {
        if ($requestedAt === null) {
            return false;
        }

        $expires = $requestedAt->modify('+' . self::TOKEN_TTL_HOURS . ' hours');

        return $expires > new \DateTimeImmutable();
    }
}

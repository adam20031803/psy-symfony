<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ForgotPasswordController extends AbstractController
{
    /** Étape 1 : formulaire email */
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function request(Request $request, UserRepository $userRepo): Response
    {
        $error   = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Veuillez saisir une adresse email valide.';
            } else {
                // On cherche l'utilisateur — mais on ne révèle PAS s'il existe
                // (bonne pratique de sécurité)
                $userRepo->findOneBy(['email' => $email]);

                // Dans tous les cas on affiche le message de succès
                $success = true;
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'error'   => $error,
            'success' => $success,
        ]);
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'app_api_login', methods: ['POST'])]
    public function apiLogin(): JsonResponse
    {
        // This code is never executed; Symfony's JSON login intercepts the request.
        return new JsonResponse(['error' => 'Invalid Request']);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
            
        ]);
    }

    #[Route('/redirect-after-login', name: 'app_redirect_after_login')]
    public function redirectAfterLogin(): Response
    {
        $user = $this->getUser();

        if ($user && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_admin');
        }

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method should never be reached.');
    }
}

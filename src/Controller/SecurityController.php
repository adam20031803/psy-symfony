<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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

    /**
     * Magic login link: authenticates the user without a password.
     * Token is valid for 15 minutes and is consumed on use.
     */
    #[Route('/magic-login/{token}', name: 'app_magic_login', methods: ['GET'])]
    public function magicLogin(
        string $token,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        Request $request,
        EventDispatcherInterface $dispatcher
    ): Response {
        // Find user by reset token
        $user = $userRepo->findOneBy(['passwordResetToken' => $token]);

        if ($user === null) {
            $this->addFlash('danger', 'Ce lien de connexion est invalide ou a déjà été utilisé.');
            return $this->redirectToRoute('app_login');
        }

        // Check token expiry (15 minutes)
        $requestedAt = $user->getPasswordResetRequestedAt();
        if ($requestedAt === null || $requestedAt->modify('+15 minutes') < new \DateTimeImmutable()) {
            $this->addFlash('danger', 'Ce lien de connexion a expiré. Veuillez demander un nouveau lien.');
            return $this->redirectToRoute('app_login');
        }

        if (!$user->isActive()) {
            $this->addFlash('danger', 'Votre compte est désactivé. Contactez l\'administrateur.');
            return $this->redirectToRoute('app_login');
        }

        // Consume the token
        $user->setPasswordResetToken(null);
        $user->setPasswordResetRequestedAt(null);
        $em->flush();

        // Authenticate the user programmatically
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));

        // Fire interactive login event
        $event = new InteractiveLoginEvent($request, $token);
        $dispatcher->dispatch($event, 'security.interactive_login');

        $this->addFlash('success', sprintf('Bienvenue, %s ! Vous êtes connecté via lien magique.', $user->getPrenom()));

        // Redirect based on role
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_admin');
        }

        return $this->redirectToRoute('app_dashboard');
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

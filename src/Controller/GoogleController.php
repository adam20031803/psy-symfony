<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenStorageInterface $tokenStorage,
        private HttpClientInterface $httpClient,
    ) {
    }

    #[Route('/connect/google', name: 'connect_google')]
    public function connectAction(Request $request): RedirectResponse
    {
        $clientId = $this->getParameter('google_client_id');
        $redirectUri = $this->generateUrl('connect_google_check', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'online',
        ];

        return $this->redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request, \Symfony\Bundle\SecurityBundle\Security $security): Response
    {
        $code = $request->query->get('code');
        $error = $request->query->get('error');

        if ($error) {
            $this->addFlash('error', 'Google authentication was cancelled or failed.');
            return $this->redirectToRoute('app_login');
        }

        if (!$code) {
            $this->addFlash('error', 'No authorization code received from Google.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Exchange code for access token
            $clientId = $this->getParameter('google_client_id');
            $clientSecret = $this->getParameter('google_client_secret');
            $redirectUri = $this->generateUrl('connect_google_check', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

            $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                ],
            ]);

            $data = $response->toArray();
            $accessToken = $data['access_token'] ?? null;

            if (!$accessToken) {
                throw new \Exception('No access token received from Google');
            }

            // Get user info
            $userResponse = $this->httpClient->request('GET', 'https://openidconnect.googleapis.com/v1/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $googleUser = $userResponse->toArray();
            $email = $googleUser['email'] ?? null;
            $name = $googleUser['name'] ?? 'User';

            if (!$email) {
                throw new \Exception('No email received from Google');
            }

            // Find or create user
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                // Create new user
                $user = new User();
                $user->setEmail($email);
                
                // Split name into first and last
                $nameParts = explode(' ', $name, 2);
                $user->setPrenom($nameParts[0] ?? 'User');
                $user->setNom($nameParts[1] ?? '');
                
                $user->setActive(true);

                // Set a random password (user won't use it since they login with Google)
                $randomPassword = bin2hex(random_bytes(32));
                $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
                $user->setPassword($hashedPassword);

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->addFlash('success', 'Bienvenue ! Votre compte avec Google a été créé.');
            }

            // Authenticate user properly using Symfony Security
            $security->login($user, 'form_login', 'main');

            // Bypass 2FA for Google login
            $request->getSession()->set('admin_2fa_unlocked', true);

            return $this->redirectToRoute('app_redirect_after_login');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Google authentication failed: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}

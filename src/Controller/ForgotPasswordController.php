<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForgotPasswordController extends AbstractController
{
    private const ALLOWED_EMAIL = 'adam.banaoues@esprit.tn';

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire('%env(MAILER_FROM)%')] string $mailerFrom,
        #[Autowire('%env(MAILER_DSN)%')] string $mailerDsn,
        #[Autowire('%env(MAILER_FORCE_TO)%')] ?string $mailerForceTo,
    ): Response {
        $error = null;
        $success = false;
        $isDev = $this->getParameter('kernel.debug');
        $isNullTransport = str_starts_with($mailerDsn, 'null://');

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Veuillez saisir une adresse email valide.';
            }
            // Check if email is allowed
            elseif (strtolower($email) !== strtolower(self::ALLOWED_EMAIL)) {
                $error = sprintf('Seule l\'adresse %s est autorisée.', self::ALLOWED_EMAIL);
            }
            // Find user
            else {
                $user = $userRepo->findOneBy(['email' => $email]);

                if ($user === null) {
                    // Pretend success for security (don't reveal if email exists)
                    $success = true;
                } else {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $user->setPasswordResetToken($token);
                    $user->setPasswordResetRequestedAt(new \DateTimeImmutable());

                    try {
                        $em->flush();
                    } catch (\Throwable $e) {
                        $logger->error('Failed to save password reset token', [
                            'exception' => $e,
                            'email' => $email,
                        ]);
                        $error = 'Une erreur est survenue. Veuillez réessayer.';
                        return $this->render('security/forgot_password.html.twig', [
                            'error' => $error,
                        ]);
                    }

                    // Generate reset URL
                    $resetUrl = $this->generateUrl(
                        'app_reset_password',
                        ['token' => $token],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    // Send email if not in test mode
                    if (!$isNullTransport) {
                        try {
                            $emailMessage = (new TemplatedEmail())
                                ->from(Address::create($mailerFrom))
                                ->to($email)
                                ->subject('Réinitialisation de votre mot de passe')
                                ->htmlTemplate('emails/reset_password.html.twig')
                                ->context([
                                    'resetUrl' => $resetUrl,
                                    'userName' => $user->getPrenom() ?? $user->getNom() ?? 'User',
                                ]);

                            $mailer->send($emailMessage);
                        } catch (TransportExceptionInterface $e) {
                            $logger->error('Failed to send password reset email', [
                                'exception' => $e,
                                'email' => $email,
                            ]);
                            // Show success anyway, not to reveal email existence
                        } catch (\Throwable $e) {
                            $logger->error('Error sending reset email', [
                                'exception' => $e,
                                'email' => $email,
                            ]);
                        }
                    }

                    $success = true;
                }
            }
            
            if ($request->isXmlHttpRequest()) {
                if ($error) {
                    return new JsonResponse(['success' => false, 'error' => $error], 400);
                }
                return new JsonResponse(['success' => true]);
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'error' => $error,
            'success' => $success,
            'isDev' => $isDev,
            'isNullTransport' => $isNullTransport,
        ]);
    }
}

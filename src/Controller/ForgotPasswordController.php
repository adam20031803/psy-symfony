<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire('%env(MAILER_FROM)%')] string $mailerFrom,
        #[Autowire('%env(MAILER_DSN)%')] string $mailerDsn,
        #[Autowire('%env(MAILER_FORCE_TO)%')] string $mailerForceTo,
    ): Response {
        $error                = null;
        $success              = false;
        $nullTransport        = str_starts_with($mailerDsn, 'null://');
        $emailSendFailed      = false;
        $emailFailureDetail   = null;
        $devResetUrl          = null;

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Veuillez saisir une adresse email valide.';
            } else {
                $user = $userRepo->findOneBy(['email' => $email]);

                if ($user === null) {
                    $success = true;
                } else {
                    $plainToken = bin2hex(random_bytes(32));
                    $user->setPasswordResetToken($plainToken);
                    $user->setPasswordResetRequestedAt(new \DateTimeImmutable());

                    try {
                        $em->flush();
                    } catch (\Throwable $e) {
                        $logger->error('Échec enregistrement jeton réinitialisation (souvent colonnes SQL manquantes)', [
                            'exception' => $e,
                            'email'     => $email,
                        ]);
                        $error = 'Impossible d’enregistrer la demande. Exécutez la mise à jour de la base (voir documentation projet) puis réessayez.';

                        return $this->render('security/forgot_password.html.twig', [
                            'error'         => $error,
                            'success'       => false,
                            'devResetUrl'   => null,
                        ]);
                    }

                    $resetUrl = $this->generateUrl(
                        'app_reset_password',
                        ['token' => $plainToken],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    if ($nullTransport && $this->getParameter('kernel.debug')) {
                        $devResetUrl = $resetUrl;
                    }

                    if (!$nullTransport && $this->mailerConfigContainsPlaceholders($mailerDsn, $mailerFrom)) {
                        $emailSendFailed = true;
                        $emailFailureDetail = 'Dans .env.local, vous avez encore des valeurs d’exemple (REMPLACE_…). Remplacez-les par votre vraie adresse Gmail (avec %40 à la place de @ dans le DSN) et le mot de passe d’application Google à 16 caractères, ou mettez MAILER_DSN=null://null le temps de configurer.';
                    } else {
                        try {
                            $accountEmail = (string) $user->getEmail();
                            $forceTo      = trim($mailerForceTo);
                            $recipient    = ($forceTo !== '' && filter_var($forceTo, FILTER_VALIDATE_EMAIL))
                                ? $forceTo
                                : $accountEmail;

                            $emailMessage = (new TemplatedEmail())
                                ->from(Address::create($mailerFrom))
                                ->to($recipient)
                                ->subject('Réinitialisation de votre mot de passe — PsyApp')
                                ->htmlTemplate('emails/reset_password.html.twig')
                                ->context([
                                    'resetUrl'            => $resetUrl,
                                    'prenom'              => $user->getPrenom() ?? '',
                                    'redirectedToMailbox' => $recipient !== $accountEmail ? $accountEmail : null,
                                ]);

                            $mailer->send($emailMessage);
                        } catch (TransportExceptionInterface $e) {
                            $logger->error('Échec envoi email réinitialisation mot de passe', [
                                'exception' => $e,
                                'email'     => $email,
                            ]);
                            $emailSendFailed = true;
                            if ($this->getParameter('kernel.debug')) {
                                $emailFailureDetail = $e->getMessage();
                            }
                        } catch (\Throwable $e) {
                            $logger->error('Erreur email réinitialisation (template ou mailer)', [
                                'exception' => $e,
                                'email'     => $email,
                            ]);
                            $emailSendFailed = true;
                            if ($this->getParameter('kernel.debug')) {
                                $emailFailureDetail = $e->getMessage();
                            }
                        }
                    }

                    $success = true;
                }
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'error'              => $error,
            'success'            => $success,
            'nullTransport'      => $nullTransport,
            'emailSendFailed'    => $emailSendFailed,
            'emailFailureDetail' => $emailFailureDetail,
            'devResetUrl'        => $devResetUrl,
        ]);
    }

    /** Détecte les modèles .env jamais remplacés (évite un appel SMTP inutile et l’erreur 535 Google). */
    private function mailerConfigContainsPlaceholders(string $dsn, string $from): bool
    {
        $blob = $dsn . ' ' . $from;
        foreach (['REMPLACE_', 'VOTRE_MDP', 'vous%40gmail.com', 'CHANGEME', 'XXXXXXXX', 'exemple@'] as $needle) {
            if (str_contains($blob, $needle)) {
                return true;
            }
        }

        return false;
    }
}

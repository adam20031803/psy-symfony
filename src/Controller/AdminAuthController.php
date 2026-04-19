<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/auth')]
class AdminAuthController extends AbstractController
{
    #[Route('/request', name: 'app_admin_auth_request')]
    public function requestAccess(Request $request, MailerInterface $mailer): Response
    {
        // If already unlocked, redirect straight to backoffice
        if ($request->getSession()->get('admin_2fa_unlocked')) {
            return $this->redirectToRoute('app_admin');
        }

        // Generate a random 4-digit code
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        
        // Store in session
        $request->getSession()->set('admin_2fa_code', $code);

        // Send Email
        $email = (new Email())
            ->from('adam.banaoues@esprit.tn')
            ->to('adam.banaoues@esprit.tn')
            ->subject('Code d\'accès au Backoffice - Atomic You')
            ->text("Votre code secret pour accéder au backoffice est : $code\n\nVeuillez le saisir sur la page de vérification.");

        try {
            $mailer->send($email);
            $this->addFlash('success', 'Un code à 4 chiffres a été envoyé à adam.banaoues@esprit.tn.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'e-mail : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_auth_verify');
    }

    #[Route('/verify', name: 'app_admin_auth_verify')]
    public function verifyAccess(Request $request): Response
    {
        $session = $request->getSession();

        // If already unlocked, redirect
        if ($session->get('admin_2fa_unlocked')) {
            return $this->redirectToRoute('app_admin');
        }

        // If no code in session, user must request it first
        if (!$session->has('admin_2fa_code')) {
            return $this->redirectToRoute('app_admin_auth_request');
        }

        if ($request->isMethod('POST')) {
            $inputCode = $request->request->get('code');
            $expectedCode = $session->get('admin_2fa_code');

            if ($inputCode === $expectedCode) {
                // Success! Set unlocked logic
                $session->set('admin_2fa_unlocked', true);
                $session->remove('admin_2fa_code');

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_admin')]);
                }

                $this->addFlash('success', 'Accès au backoffice déverrouillé avec succès !');
                return $this->redirectToRoute('app_admin'); // Or whatever the dashboard route is named
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'error' => 'Code incorrect. Veuillez réessayer.']);
            }

            $this->addFlash('error', 'Code incorrect. Veuillez réessayer.');
        }

        return $this->render('admin/auth/verify_code.html.twig');
    }
}

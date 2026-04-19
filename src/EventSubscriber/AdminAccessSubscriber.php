<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdminAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $router
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // High priority to intercept before controllers are executed
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 30],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Only trigger on the main request (not sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // If the user navigates OUTSIDE the admin zone (and it's not a dev/asset route), re-lock the backoffice
        if (!str_starts_with($path, '/admin')) {
            if (!str_starts_with($path, '/_') && !str_starts_with($path, '/css') && !str_starts_with($path, '/js') && !str_starts_with($path, '/api') && !str_starts_with($path, '/redirect-after-login')) {
                $request->getSession()->remove('admin_2fa_unlocked');
            }
            return;
        }

        // Allow access to the 2FA auth routes to avoid infinite redirect loops
        if (str_starts_with($path, '/admin/auth/')) {
            return;
        }

        $session = $request->getSession();

        // Check user unlocks
        if (!$session->get('admin_2fa_unlocked', false)) {
            // Redirect to the request access page which sends the 4 digit code
            $url = $this->router->generate('app_admin_auth_request');
            $event->setResponse(new RedirectResponse($url));
        }
    }
}

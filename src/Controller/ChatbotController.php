<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ChatbotController extends AbstractController
{
    #[Route('/chatbot', name: 'app_chatbot', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dashboard/chatbot.html.twig', [
            'active_section' => 'chatbot',
        ]);
    }
}

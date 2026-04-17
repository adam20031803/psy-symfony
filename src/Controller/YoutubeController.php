<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[Route('/videos')]
#[IsGranted('ROLE_USER')]
class YoutubeController extends AbstractController
{
    #[Route('/', name: 'app_youtube_index', methods: ['GET'])]
    public function index(
        #[Autowire('%env(YOUTUBE_API_KEY)%')] string $apiKey
    ): Response {
        return $this->render('youtube/index.html.twig', [
            'youtube_api_key' => $apiKey,
        ]);
    }
}

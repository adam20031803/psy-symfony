<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

#[Route('/api/face', name: 'api_face_')]
class FaceAuthController extends AbstractController
{
    /**
     * POST /api/face/register
     *
     * Saves the face descriptor for the currently logged-in user.
     * Body: { "descriptor": [128 floats] }
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;

        if (!is_array($descriptor) || count($descriptor) !== 128) {
            return $this->json([
                'success' => false,
                'error'   => 'Descripteur facial invalide (128 valeurs requises)',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Ensure all values are floats
        $descriptor = array_map('floatval', $descriptor);

        $user->setFaceDescriptor($descriptor);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Visage enregistré avec succès']);
    }

    /**
     * POST /api/face/login
     *
     * Finds and logs in the user whose stored face descriptor is closest
     * to the submitted descriptor (Euclidean distance < 0.6 threshold).
     * Body: { "descriptor": [128 floats] }
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        Security $security
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $submitted = $data['descriptor'] ?? null;

        if (!is_array($submitted) || count($submitted) !== 128) {
            return $this->json([
                'success' => false,
                'error'   => 'Descripteur facial invalide',
            ], Response::HTTP_BAD_REQUEST);
        }

        $submitted = array_map('floatval', $submitted);

        // Load all users that have a registered face
        /** @var User[] $candidates */
        $candidates = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.faceDescriptor IS NOT NULL')
            ->getQuery()
            ->getResult();

        $bestUser     = null;
        $bestDistance = PHP_FLOAT_MAX;
        $threshold    = 0.6; // face-api.js default similarity threshold

        foreach ($candidates as $candidate) {
            $stored = $candidate->getFaceDescriptor();
            if (!is_array($stored) || count($stored) !== 128) {
                continue;
            }

            $distance = $this->euclideanDistance($submitted, $stored);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestUser     = $candidate;
            }
        }

        if ($bestUser === null || $bestDistance > $threshold) {
            return $this->json([
                'success'  => false,
                'error'    => 'Visage non reconnu',
                'distance' => $bestDistance,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Log the user in by creating a session
        $security->login($bestUser, 'form_login', 'main');

        return $this->json([
            'success'      => true,
            'message'      => 'Connexion réussie',
            'redirect'     => $bestUser->isAdmin() ? '/admin' : '/dashboard',
            'distance'     => round($bestDistance, 4),
        ]);
    }

    /**
     * POST /api/face/status
     *
     * Returns whether the current user has a face descriptor registered.
     */
    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['registered' => false]);
        }

        return $this->json([
            'registered' => $user->getFaceDescriptor() !== null,
        ]);
    }

    // -----------------------------------------------------------------
    //  Helpers
    // -----------------------------------------------------------------

    /**
     * Computes the Euclidean distance between two 128-float descriptors.
     * Lower = more similar (0 = identical).
     */
    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $diff = ($a[$i] ?? 0.0) - ($b[$i] ?? 0.0);
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }
}

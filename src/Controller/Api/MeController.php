<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Minimal "who am I" endpoint for the mobile app.
 */
final class MeController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(): JsonResponse
    {
        // The IsGranted check should ensure a User is present; this is a safe fallback.
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
        ]);
    }
}

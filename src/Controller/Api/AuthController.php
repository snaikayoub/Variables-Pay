<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Auth endpoints are handled by the Security system (LexikJWT + refresh tokens).
 *
 * These controller actions act as routing placeholders only: when the firewall
 * is configured correctly, the request is intercepted before reaching them.
 * Returning 401 here makes misconfiguration obvious.
 */
final class AuthController extends AbstractController
{
    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): Response
    {
        // Handled by the json_login firewall.
        return new Response('', Response::HTTP_UNAUTHORIZED);
    }

    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(): Response
    {
        // Handled by the refresh token authenticator.
        return new Response('', Response::HTTP_UNAUTHORIZED);
    }
}

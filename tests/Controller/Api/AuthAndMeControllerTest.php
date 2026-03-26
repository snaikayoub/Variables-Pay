<?php

namespace App\Tests\Controller\Api;

use App\Tests\Support\ApiTestCase;

final class AuthAndMeControllerTest extends ApiTestCase
{
    public function testMeIsUnauthorizedWithoutToken(): void
    {
        $client = $this->client();
        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginReturnsJwtAndMeReturnsUserData(): void
    {
        $email = 'test@example.com';
        $password = 'test-password';

        $this->createUser($email, $password, ['ROLE_RESPONSABLE_SERVICE']);

        $client = $this->client();
        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => $password,
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('token', $payload);
        $this->assertNotEmpty($payload['token']);

        $token = (string) $payload['token'];

        $client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();

        $me = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($me);
        $this->assertSame($email, $me['email'] ?? null);
        $this->assertIsArray($me['roles'] ?? null);
    }
}

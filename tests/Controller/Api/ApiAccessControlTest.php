<?php

namespace App\Tests\Controller\Api;

use App\Tests\Support\ApiTestCase;

final class ApiAccessControlTest extends ApiTestCase
{
    public function testServiceEndpointsReturn403WhenRoleMissing(): void
    {
        $email = 'basic.user@example.com';
        $password = 'pw';

        $this->createUser($email, $password, ['ROLE_USER']);
        $token = $this->loginToken($email, $password);

        $this->client()->request('GET', '/api/responsable/service/voyages?typePaie=mensuelle', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseStatusCodeSame(403);

        $this->client()->request('GET', '/api/responsable/service/prime-performance?typePaie=mensuelle', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseStatusCodeSame(403);

        $this->client()->request('GET', '/api/responsable/service/prime-fonction?typePaie=mensuelle', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDivisionEndpointsReturn403WhenRoleMissing(): void
    {
        $email = 'basic.user2@example.com';
        $password = 'pw';

        $this->createUser($email, $password, ['ROLE_USER']);
        $token = $this->loginToken($email, $password);

        $this->client()->request('GET', '/api/responsable/division/voyages?typePaie=mensuelle', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseStatusCodeSame(403);

        $this->client()->request('GET', '/api/responsable/division/prime-performance?typePaie=mensuelle', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseStatusCodeSame(403);

        $this->client()->request('GET', '/api/responsable/division/prime-fonction?typePaie=mensuelle', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseStatusCodeSame(403);
    }
}

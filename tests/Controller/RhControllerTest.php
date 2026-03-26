<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RhControllerTest extends WebTestCase
{
    public function testRhDashboardRedirectsWhenNotLoggedIn(): void
    {
        $client = static::createClient();
        
        // Simule la visite d'un utilisateur anonyme
        $client->request('GET', '/rh/');

        // On s'attend à être redirigé vers la page de login car l'URL /rh/ est protégée
        $this->assertResponseRedirects('/login');
    }
}

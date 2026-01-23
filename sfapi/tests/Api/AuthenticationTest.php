<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class AuthenticationTest extends ApiTestCase
{
    // Test de l'inscription
    public function testRegistration(): void
    {
        $client = static::createClient();
        $email = 'register-' . uniqid() . '@test.com';

        $client->request('POST', '/api/users', [
            // IMPORTANT : On précise qu'on envoie du JSON-LD
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => $email,
                'plainPassword' => 'password123'
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'email' => $email
        ]);
    }

    // Test du Login
    public function testLogin(): void
    {
        $client = static::createClient();

        // 1. On crée un user via l'API d'abord
        $email = 'login-' . uniqid() . '@test.com';
        $password = 'password123';

        $client->request('POST', '/api/users', [
            // CORRECTION ICI : Ajout du header manquant qui causait l'erreur
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => $email, 'plainPassword' => $password]
        ]);

        // Vérification rapide que l'utilisateur est bien créé (Optionnel mais utile pour debug)
        $this->assertResponseStatusCodeSame(201);

        // 2. On tente de se connecter
        // Note: /api/login_check accepte le JSON standard, lui !
        $response = $client->request('POST', '/api/login_check', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $email,
                'password' => $password
            ]
        ]);

        $data = $response->toArray();
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']); // Vérifie que la clé "token" existe
    }

    // Test accès refusé
    public function testProtectedResourceWithoutToken(): void
    {
        $client = static::createClient();
        // On tente d'accéder sans token -> 401
        $client->request('GET', '/api/games');
        $this->assertResponseStatusCodeSame(401);
    }
}

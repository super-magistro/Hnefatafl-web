<?php

namespace App\Tests\Api;

use App\Entity\User;

trait AuthenticationTestTrait
{
    /**
     * Crée un token JWT pour l'utilisateur et configure le client pour l'utiliser.
     */
    protected function createClientWithCredentials(string $email = 'test@example.com', string $password = 'password'): \ApiPlatform\Symfony\Bundle\Test\Client
    {
        $client = static::createClient();

        // 1. On récupère le token via l'API
        $response = $client->request('POST', '/api/login_check', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $email,
                'password' => $password
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $token = $data['token'];

        // 2. On configure le client par défaut avec le Header Authorization
        $client->setDefaultOptions([
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        return $client;
    }
}

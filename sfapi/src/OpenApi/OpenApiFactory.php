<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('api_platform.openapi.factory')]
class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        // 1. On définit le schéma de sécurité (Le composant "Bearer")
        $securitySchemes = $openApi->getComponents()->getSecuritySchemes();
        $securitySchemes['bearerAuth'] = new \ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ]);

        // 2. On applique la sécurité globalement à toute l'API
        // Cela ajoute le cadenas à toutes les routes
        $security = $openApi->getSecurity();
        $security[] = new \ArrayObject([
            'bearerAuth' => [],
        ]);

        return $openApi->withSecurity($security);
    }
}

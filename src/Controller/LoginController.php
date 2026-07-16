<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

class LoginController extends AbstractController
{

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        operationId: 'login',
        description: 'Authentifie un utilisateur et retourne un token JWT.',
        summary: 'Authentification utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(
                        property: 'username',
                        type: 'string',
                        example: 'john'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        example: 'MotDePasse123'
                    )
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authentification réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'token',
                            type: 'string',
                            example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Identifiants invalides'
            )
        ]
    )]
    public function login(): void
    {
        throw new \LogicException('Cette méthode ne devrait jamais être appelée.');
    }
}

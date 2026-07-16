<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Home Inventory API',
    version: '1.0.0',
    description: 'API REST permettant de gérer des maisons, murs, zones de stockage, objets et thèmes.'
)]

#[OA\Server(
    url: 'https://localhost',
    description: 'Serveur de développement'
)]

#[OA\SecurityScheme(
    securityScheme: 'Bearer',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]

#[OA\Tag(
    name: 'Authentication',
    description: 'Authentification JWT'
)]

#[OA\Tag(
    name: 'Users',
    description: 'Gestion des utilisateurs'
)]

#[OA\Tag(
    name: 'Houses',
    description: 'Gestion des maisons'
)]

#[OA\Tag(
    name: 'Storages',
    description: 'Gestion des zones de stockage'
)]

#[OA\Tag(
    name: 'Walls',
    description: 'Gestion des murs'
)]

#[OA\Tag(
    name: 'Items',
    description: 'Gestion des objets'
)]

#[OA\Tag(
    name: 'Themes',
    description: 'Gestion des thèmes utilisateur'
)]

final class ApiDocumentation
{
}

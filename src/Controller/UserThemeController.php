<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserTheme;
use App\Repository\UserThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[Route('/api/themes')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class UserThemeController extends AbstractController
{
    #[OA\Get(
        path: '/api/themes',
        operationId: 'getThemes',
        description: 'Retourne tous les thèmes appartenant à l’utilisateur connecté.',
        summary: 'Liste des thèmes utilisateur',
        security: [['Bearer' => []]],
        tags: ['Themes'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des thèmes',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Cuisine'),
                            new OA\Property(property: 'color', type: 'string', example: '#FF9800')
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(UserThemeRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $themes = $repository->findBy([
            'userEntity' => $user
        ]);

        $data = [];

        foreach ($themes as $theme) {
            $data[] = [
                'id' => $theme->getId(),
                'name' => $theme->getName(),
                'color' => $theme->getColor(),
            ];
        }

        return $this->json($data);
    }

    #[OA\Get(
        path: '/api/themes/{id}',
        operationId: 'getTheme',
        summary: 'Afficher un thème',
        security: [['Bearer' => []]],
        tags: ['Themes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Identifiant du thème',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thème trouvé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'color', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Thème introuvable')
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(UserTheme $theme): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($theme->getUserEntity() !== $user) {
            return $this->json([
                'message' => 'Forbidden'
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $theme->getId(),
            'name' => $theme->getName(),
            'color' => $theme->getColor(),
        ]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        path: '/api/themes',
        operationId: 'createTheme',
        summary: 'Créer un thème',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'color'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Garage'
                    ),
                    new OA\Property(
                        property: 'color',
                        type: 'string',
                        example: '#2196F3'
                    )
                ]
            )
        ),
        tags: ['Themes'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Thème créé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'color', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Requête invalide'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        $theme = new UserTheme();
        $theme->setName($data['name']);
        $theme->setColor($data['color']);
        $theme->setUserEntity($user);

        $em->persist($theme);
        $em->flush();

        return $this->json([
            'id' => $theme->getId(),
            'name' => $theme->getName(),
            'color' => $theme->getColor(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/themes/{id}',
        operationId: 'updateTheme',
        summary: 'Modifier un thème',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'color', type: 'string', example: '#4CAF50')
                ]
            )
        ),
        tags: ['Themes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thème modifié',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Theme updated'
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Thème introuvable')
        ]
    )]
    public function update(
        UserTheme $theme,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($theme->getUserEntity() !== $user) {
            return $this->json([
                'message' => 'Forbidden'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $theme->setName($data['name']);
        }

        if (isset($data['color'])) {
            $theme->setColor($data['color']);
        }

        $em->flush();

        return $this->json([
            'message' => 'Theme updated'
        ]);
    }

    #[OA\Delete(
        path: '/api/themes/{id}',
        operationId: 'deleteTheme',
        summary: 'Supprimer un thème',
        security: [['Bearer' => []]],
        tags: ['Themes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thème supprimé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Theme deleted'
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Thème introuvable')
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        UserTheme $theme,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($theme->getUserEntity() !== $user) {
            return $this->json([
                'message' => 'Forbidden'
            ], Response::HTTP_FORBIDDEN);
        }

        $em->remove($theme);
        $em->flush();

        return $this->json([
            'message' => 'Theme deleted'
        ]);
    }
}

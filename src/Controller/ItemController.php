<?php

namespace App\Controller\Api;

use App\Entity\Item;
use App\Entity\StorageBox;
use App\Entity\User;
use App\Repository\UserThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[Route('/api')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ItemController extends AbstractController
{
    #[OA\Get(
        path: '/api/storages/{id}/items',
        operationId: 'getStorageItems',
        summary: 'Liste les objets d’un stockage',
        security: [['Bearer' => []]],
        tags: ['Items'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Identifiant du stockage',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des objets',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Perceuse'),
                            new OA\Property(property: 'floor', type: 'integer', example: 2),
                            new OA\Property(property: 'number', type: 'integer', example: 1),
                            new OA\Property(property: 'themeId', type: 'integer', example: 3, nullable: true)
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Stockage introuvable')
        ]
    )]
    #[Route('/storages/{id}/items', methods: ['GET'])]
    public function index(StorageBox $storage): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($storage->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = [];

        foreach ($storage->getItems() as $item) {
            $data[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'floor' => $item->getFloor(),
                'number' => $item->getNumber(),
                'themeId' => $item->getTheme()?->getId(),
            ];
        }

        return $this->json($data);
    }


    #[OA\Post(
        path: '/api/storages/{id}/items',
        operationId: 'createItem',
        summary: 'Créer un objet',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'floor'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Tournevis'
                    ),
                    new OA\Property(
                        property: 'floor',
                        type: 'integer',
                        example: 1
                    ),
                    new OA\Property(
                        property: 'number',
                        type: 'integer',
                        example: 1
                    ),
                    new OA\Property(
                        property: 'themeId',
                        type: 'integer',
                        nullable: true,
                        example: 2
                    )
                ]
            )
        ),
        tags: ['Items'],
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
                response: 201,
                description: 'Objet créé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 15)
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Requête invalide'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    #[Route('/storages/{id}/items', methods: ['POST'])]
    public function create(
        StorageBox $storage,
        Request $request,
        UserThemeRepository $themeRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($storage->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        $item = new Item();
        $item->setStorageBox($storage);
        $item->setName($data['name']);
        $item->setFloor($data['floor']);
        $item->setNumber($data['number']);

        if (!empty($data['themeId'])) {
            $theme = $themeRepository->find($data['themeId']);

            if (!$theme || $theme->getUser() !== $user) {
                return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
            }

            $item->setTheme($theme);
        }

        $em->persist($item);
        $em->flush();

        return $this->json([
            'id' => $item->getId(),
        ], Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/api/items/{id}',
        operationId: 'getItem',
        summary: 'Afficher un objet',
        security: [['Bearer' => []]],
        tags: ['Items'],
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
                description: 'Objet trouvé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'floor', type: 'integer'),
                        new OA\Property(property: 'number', type: 'integer'),
                        new OA\Property(property: 'themeId', type: 'integer', nullable: true)
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Objet introuvable')
        ]
    )]
    #[Route('/items/{id}', methods: ['GET'])]
    public function show(Item $item): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($item->getStorageBox()->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $item->getId(),
            'name' => $item->getName(),
            'floor' => $item->getFloor(),
            'number' => $item->getNumber(),
            'themeId' => $item->getTheme()?->getId(),
        ]);
    }

    #[OA\Put(
        path: '/api/items/{id}',
        operationId: 'updateItem',
        summary: 'Modifier un objet',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'floor', type: 'integer'),
                    new OA\Property(property: 'number', type: 'integer'),
                    new OA\Property(
                        property: 'themeId',
                        type: 'integer',
                        nullable: true,
                        description: 'Mettre null pour retirer le thème'
                    )
                ]
            )
        ),
        tags: ['Items'],
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
                description: 'Objet modifié',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Item updated'
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Objet introuvable')
        ]
    )]
    #[Route('/items/{id}', methods: ['PUT'])]
    public function update(
        Item $item,
        Request $request,
        UserThemeRepository $themeRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($item->getStorageBox()->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $item->setName($data['name']);
        }

        if (isset($data['floor'])) {
            $item->setFloor($data['floor']);
        }

        if (isset($data['number'])) {
            $item->setNumber($data['floor']);
        }

        if (array_key_exists('themeId', $data)) {

            if ($data['themeId'] === null) {
                $item->setTheme(null);
            } else {
                $theme = $themeRepository->find($data['themeId']);

                if (!$theme || $theme->getUser() !== $user) {
                    return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
                }

                $item->setTheme($theme);
            }
        }

        $em->flush();

        return $this->json([
            'message' => 'Item updated'
        ]);
    }


    #[OA\Delete(
        path: '/api/items/{id}',
        operationId: 'deleteItem',
        summary: 'Supprimer un objet',
        security: [['Bearer' => []]],
        tags: ['Items'],
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
                description: 'Objet supprimé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Item deleted'
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Objet introuvable')
        ]
    )]
    #[Route('/items/{id}', methods: ['DELETE'])]
    public function delete(
        Item $item,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($item->getStorageBox()->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($item);
        $em->flush();

        return $this->json([
            'message' => 'Item deleted'
        ]);
    }
}

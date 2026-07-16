<?php

namespace App\Controller;

use App\Entity\House;
use App\Entity\StorageBox;
use App\Entity\User;
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
class StorageBoxController extends AbstractController
{
    #[OA\Get(
        path: '/api/houses/{id}/storages',
        description: 'Retourne toutes les zones de stockage appartenant à une maison de l’utilisateur connecté.',
        summary: 'Liste des zones de stockage d’une maison',
        security: [['Bearer' => []]],
        tags: ['Storages'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Identifiant de la maison',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des stockages',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Garage'),
                            new OA\Property(property: 'posX', type: 'number', format: 'float', example: 100),
                            new OA\Property(property: 'posY', type: 'number', format: 'float', example: 80),
                            new OA\Property(property: 'posX2', type: 'number', format: 'float', example: 250),
                            new OA\Property(property: 'posY2', type: 'number', format: 'float', example: 180),
                            new OA\Property(property: 'image', type: 'string', example: 'garage.png')
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Maison introuvable')
        ]
    )]
    #[Route('/houses/{id}/storages', methods: ['GET'])]
    public function index(House $house): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($house->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = [];

        foreach ($house->getStorages() as $storage) {
            $data[] = [
                'id' => $storage->getId(),
                'name' => $storage->getName(),
                'posX' => $storage->getPosX(),
                'posY' => $storage->getPosY(),
                'posX2' => $storage->getPosX2(),
                'posY2' => $storage->getPosY2(),
                'image' => $storage->getImage(),
            ];
        }

        return $this->json($data);
    }

    #[OA\Post(
        path: '/api/houses/{id}/storages',
        summary: 'Créer une zone de stockage',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name','posX','posY','posX2','posY2','image'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Garage'),
                    new OA\Property(property: 'posX', type: 'number', format: 'float'),
                    new OA\Property(property: 'posY', type: 'number', format: 'float'),
                    new OA\Property(property: 'posX2', type: 'number', format: 'float'),
                    new OA\Property(property: 'posY2', type: 'number', format: 'float'),
                    new OA\Property(property: 'image', type: 'string', example: 'garage.png')
                ]
            )
        ),
        tags: ['Storages'],
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
                description: 'Zone créée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 5)
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Requête invalide'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    #[Route('/houses/{id}/storages', methods: ['POST'])]
    public function create(
        House $house,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($house->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        $storage = new StorageBox();
        $storage->setHouse($house);
        $storage->setName($data['name']);
        $storage->setPosX($data['posX']);
        $storage->setPosY($data['posY']);
        $storage->setPosX2($data['posX2']);
        $storage->setPosY2($data['posY2']);
        $storage->setImage($data['image']);

        $em->persist($storage);
        $em->flush();

        return $this->json([
            'id' => $storage->getId(),
        ], Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/api/storages/{id}',
        summary: 'Détail d’une zone de stockage',
        security: [['Bearer' => []]],
        tags: ['Storages'],
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
                description: 'Zone trouvée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'posX', type: 'number'),
                        new OA\Property(property: 'posY', type: 'number'),
                        new OA\Property(property: 'posX2', type: 'number'),
                        new OA\Property(property: 'posY2', type: 'number'),
                        new OA\Property(property: 'image', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Storage introuvable')
        ]
    )]
    #[Route('/storages/{id}', methods: ['GET'])]
    public function show(StorageBox $storage): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($storage->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $storage->getId(),
            'name' => $storage->getName(),
            'posX' => $storage->getPosX(),
            'posY' => $storage->getPosY(),
            'posX2' => $storage->getPosX2(),
            'posY2' => $storage->getPosY2(),
            'image' => $storage->getImage(),
        ]);
    }

    #[OA\Put(
        path: '/api/storages/{id}',
        summary: 'Modifier une zone de stockage',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'posX', type: 'number'),
                    new OA\Property(property: 'posY', type: 'number'),
                    new OA\Property(property: 'posX2', type: 'number'),
                    new OA\Property(property: 'posY2', type: 'number'),
                    new OA\Property(property: 'image', type: 'string')
                ]
            )
        ),
        tags: ['Storages'],
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
                description: 'Zone modifiée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Storage updated')
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Storage introuvable')
        ]
    )]
    #[Route('/storages/{id}', methods: ['PUT'])]
    public function update(
        StorageBox $storage,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($storage->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $storage->setName($data['name']);
        }

        if (isset($data['posX'])) {
            $storage->setPosX($data['posX']);
        }

        if (isset($data['posY'])) {
            $storage->setPosY($data['posY']);
        }

        if (isset($data['posX2'])) {
            $storage->setPosX2($data['posX2']);
        }

        if (isset($data['posY2'])) {
            $storage->setPosY2($data['posY2']);
        }

        if (isset($data['image'])) {
            $storage->setImage($data['image']);
        }

        $em->flush();

        return $this->json([
            'message' => 'Storage updated'
        ]);
    }

    #[OA\Delete(
        path: '/api/storages/{id}',
        summary: 'Supprimer une zone de stockage',
        security: [['Bearer' => []]],
        tags: ['Storages'],
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
                description: 'Zone supprimée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Storage deleted')
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Storage introuvable')
        ]
    )]
    #[Route('/storages/{id}', methods: ['DELETE'])]
    public function delete(
        StorageBox $storage,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($storage->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($storage);
        $em->flush();

        return $this->json([
            'message' => 'Storage deleted'
        ]);
    }
}

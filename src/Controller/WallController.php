<?php

namespace App\Controller;

use App\Entity\House;
use App\Entity\User;
use App\Entity\Wall;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class WallController extends AbstractController
{

    #[OA\Get(
        path: '/api/houses/{id}/walls',
        operationId: 'getHouseWalls',
        summary: 'Liste des murs d’une maison',
        security: [['Bearer' => []]],
        tags: ['Walls'],
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
                description: 'Liste des murs',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'posX', type: 'number', format: 'float', example: 150),
                            new OA\Property(property: 'posY', type: 'number', format: 'float', example: 50),
                            new OA\Property(property: 'posX2', type: 'number', format: 'float', example: 300),
                            new OA\Property(property: 'posY2', type: 'number', format: 'float', example: 50),
                            new OA\Property(property: 'image', type: 'string', example: 'wall.png')
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Maison introuvable')
        ]
    )]
    #[Route('/api/houses/{id}/walls', methods: ['GET'])]
    public function index(House $house): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($house->getUserEntity() !== $user) {
            return $this->json([
                'message' => 'Forbidden'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = [];

        foreach ($house->getWalls() as $wall) {
            $data[] = [
                'id' => $wall->getId(),
                'posX' => $wall->getPosX(),
                'posY' => $wall->getPosY(),
                'posX2' => $wall->getPosX2(),
                'posY2' => $wall->getPosY2(),
                'image' => $wall->getImage(),
            ];
        }

        return $this->json($data);
    }

    #[OA\Get(
        path: '/api/walls/{id}',
        operationId: 'getWall',
        summary: 'Détail d’un mur',
        security: [['Bearer' => []]],
        tags: ['Walls'],
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
                description: 'Mur trouvé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'posX', type: 'number'),
                        new OA\Property(property: 'posY', type: 'number'),
                        new OA\Property(property: 'posX2', type: 'number'),
                        new OA\Property(property: 'posY2', type: 'number'),
                        new OA\Property(property: 'image', type: 'string'),
                        new OA\Property(property: 'houseId', type: 'integer')
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Mur introuvable')
        ]
    )]
    #[Route('/api/walls/{id}', methods: ['GET'])]
    public function show(Wall $wall): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($wall->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $wall->getId(),
            'posX' => $wall->getPosX(),
            'posY' => $wall->getPosY(),
            'posX2' => $wall->getPosX2(),
            'posY2' => $wall->getPosY2(),
            'image' => $wall->getImage(),
            'houseId' => $wall->getHouse()->getId(),
        ]);
    }

    #[OA\Post(
        path: '/api/houses/{id}/walls',
        operationId: 'createWall',
        summary: 'Créer un mur',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['posX','posY','posX2','posY2','image'],
                properties: [
                    new OA\Property(property: 'posX', type: 'number'),
                    new OA\Property(property: 'posY', type: 'number'),
                    new OA\Property(property: 'posX2', type: 'number'),
                    new OA\Property(property: 'posY2', type: 'number'),
                    new OA\Property(property: 'image', type: 'string', example: 'wall.png')
                ]
            )
        ),
        tags: ['Walls'],
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
                description: 'Mur créé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 12)
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Requête invalide'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    #[Route('/api/houses/{id}/walls', methods: ['POST'])]
    public function create(
        House $house,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($house->getUserEntity() !== $user) {
            return $this->json([
                'message' => 'Forbidden'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        $wall = new Wall();
        $wall->setHouse($house);
        $wall->setPosX($data['posX']);
        $wall->setPosY($data['posY']);
        $wall->setPosX2($data['posX2']);
        $wall->setPosY2($data['posY2']);
        $wall->setImage($data['image']);

        $em->persist($wall);
        $em->flush();

        return $this->json([
            'id' => $wall->getId()
        ], Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: '/api/walls/{id}',
        operationId: 'updateWall',
        summary: 'Modifier un mur',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'posX', type: 'number'),
                    new OA\Property(property: 'posY', type: 'number'),
                    new OA\Property(property: 'posX2', type: 'number'),
                    new OA\Property(property: 'posY2', type: 'number'),
                    new OA\Property(property: 'image', type: 'string')
                ]
            )
        ),
        tags: ['Walls'],
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
                description: 'Mur modifié',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Wall updated')
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Mur introuvable')
        ]
    )]
    #[Route('/api/walls/{id}', methods: ['PUT'])]
    public function update(
        Wall $wall,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($wall->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['posX'])) {
            $wall->setPosX($data['posX']);
        }

        if (isset($data['posY'])) {
            $wall->setPosY($data['posY']);
        }

        if (isset($data['posX2'])) {
            $wall->setPosX2($data['posX2']);
        }

        if (isset($data['posY2'])) {
            $wall->setPosY2($data['posY2']);
        }

        if (isset($data['image'])) {
            $wall->setImage($data['image']);
        }

        $em->flush();

        return $this->json([
            'message' => 'Wall updated'
        ]);
    }

    #[OA\Delete(
        path: '/api/walls/{id}',
        operationId: 'deleteWall',
        summary: 'Supprimer un mur',
        security: [['Bearer' => []]],
        tags: ['Walls'],
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
                description: 'Mur supprimé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Wall deleted')
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Mur introuvable')
        ]
    )]
    #[Route('/api/walls/{id}', methods: ['DELETE'])]
    public function delete(
        Wall $wall,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($wall->getHouse()->getUserEntity() !== $user) {
            return $this->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($wall);
        $em->flush();

        return $this->json([
            'message' => 'Wall deleted'
        ]);
    }
}

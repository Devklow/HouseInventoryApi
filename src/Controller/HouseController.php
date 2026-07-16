<?php

namespace App\Controller;

use App\Entity\House;
use App\Entity\User;
use App\Repository\HouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[Route('/api/houses')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class HouseController extends AbstractController
{

    #[OA\Get(
        path: '/api/houses',
        description: 'Retourne toutes les maisons appartenant à l’utilisateur connecté.',
        summary: 'Liste des maisons',
        security: [['Bearer' => []]],
        tags: ['Houses'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des maisons',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Maison'),
                            new OA\Property(property: 'background', type: 'string', example: 'house.png')
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(HouseRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $houses = $repository->findBy([
            'userEntity' => $user
        ]);

        $data = [];

        foreach ($houses as $house) {
            $data[] = [
                'id' => $house->getId(),
                'name' => $house->getName(),
                'background' => $house->getBackground(),
            ];
        }

        return $this->json($data);
    }

    #[OA\Get(
        path: '/api/houses/{id}',
        summary: 'Détail d’une maison',
        security: [['Bearer' => []]],
        tags: ['Houses'],
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
                description: 'Maison trouvée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'background', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Maison introuvable')
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(House $house): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($house->getUserEntity() !== $user) {
            return $this->json([
                'message' => 'Forbidden'
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $house->getId(),
            'name' => $house->getName(),
            'background' => $house->getBackground(),
        ]);
    }

    #[OA\Post(
        path: '/api/houses',
        summary: 'Créer une maison',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'background'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Appartement'
                    ),
                    new OA\Property(
                        property: 'background',
                        type: 'string',
                        example: 'background.jpg'
                    )
                ]
            )
        ),
        tags: ['Houses'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Maison créée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'background', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Requête invalide'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    #[Route('', methods: ['POST'])]
    public function create(
        Request                $request,
        EntityManagerInterface $em
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        $house = new House();
        $house->setName($data['name']);
        $house->setBackground($data['background']);
        $house->setUserEntity($user);

        $em->persist($house);
        $em->flush();

        return $this->json([
            'id' => $house->getId(),
            'name' => $house->getName(),
            'background' => $house->getBackground(),
        ], Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: '/api/houses/{id}',
        summary: 'Modifier une maison',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'background', type: 'string')
                ]
            )
        ),
        tags: ['Houses'],
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
                description: 'Maison modifiée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'House updated'
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Maison introuvable')
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        House                  $house,
        Request                $request,
        EntityManagerInterface $em
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($house->getUserEntity() !== $user) {
            return $this->json([
                'message' => 'Forbidden'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $house->setName($data['name']);
        }

        if (isset($data['background'])) {
            $house->setBackground($data['background']);
        }

        $em->flush();

        return $this->json([
            'message' => 'Maison mise à jour'
        ]);
    }

    #[OA\Delete(
        path: '/api/houses/{id}',
        summary: 'Supprimer une maison',
        security: [['Bearer' => []]],
        tags: ['Houses'],
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
                description: 'Maison supprimée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Maison supprimée'
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Maison introuvable')
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        House                  $house,
        EntityManagerInterface $em
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($house->getUserEntity() !== $user) {
            return $this->json([
                'message' => 'Forbidden'
            ], Response::HTTP_FORBIDDEN);
        }

        $em->remove($house);
        $em->flush();

        return $this->json([
            'message' => 'Maison supprimée'
        ]);
    }
}

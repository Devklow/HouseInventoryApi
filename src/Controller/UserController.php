<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/users')]
class UserController extends AbstractController
{
    /**
     * Vérifie que l'utilisateur connecté est bien celui ciblé.
     */
    #[OA\Get(
        path: '/api/users',
        operationId: 'getUsers',
        summary: 'Liste des utilisateurs (dev uniquement)',
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des utilisateurs',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'username', type: 'string', example: 'john')
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    private function denyAccessUnlessOwner(User $user): void
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User || $currentUser->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
    }

    #[Route('', methods: ['GET'])]
    public function index(UserRepository $repository): JsonResponse
    {
        // La liste des utilisateurs n'est accessible qu'en environnement dev
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $users = $repository->findAll();
        $users = $repository->findAll();

        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ];
        }

        return $this->json($data);
    }


    #[OA\Get(
        path: '/api/users/{id}',
        operationId: 'getUser',
        summary: 'Afficher son profil',
        security: [['Bearer' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: array(
            new OA\Response(
                response: 200,
                description: 'Utilisateur trouvé',
                content: new OA\JsonContent(
                    properties: array(
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'username', type: 'string')
                    )
                )
            ),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable')
        )
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        // On ne peut consulter que son propre compte
        try {
            $this->denyAccessUnlessOwner($user);
        } catch (AccessDeniedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
        ]);
    }


    #[OA\Post(
        path: '/api/users',
        operationId: 'registerUser',
        summary: 'Créer un compte',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'pass'],
                properties: [
                    new OA\Property(
                        property: 'username',
                        type: 'string',
                        example: 'john'
                    ),
                    new OA\Property(
                        property: 'pass',
                        type: 'string',
                        format: 'password',
                        example: 'MotDePasse123'
                    )
                ]
            )
        ),
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur créé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Utilisateur créé'
                        ),
                        new OA\Property(
                            property: 'id',
                            type: 'integer',
                            example: 8
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Mot de passe invalide'
            ),
            new OA\Response(
                response: 409,
                description: 'Pseudo déjà utilisé'
            )
        ]
    )]
    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        PasswordHasherFactoryInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $errors = $validator->validate(
            $data['pass'],
            [
                new NotBlank(),
                new Length(min: 12, minMessage: "Le mot de passe doit contenir au moins 12 caractères"),
                new Regex(
                    pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                    message: 'Le mot de passe doit contenir une majuscule, une minuscule et un chiffre.'
                ),
            ]
        );

        if (count($errors) > 0) {
            return $this->json([
                'message' => $errors[0]->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setPass(
            $passwordHasher
                ->getPasswordHasher($user)
                ->hash($data['pass'])
        );

        try {
            $em->persist($user);
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            return $this->json([
                'message' => 'Ce pseudo est déjà utilisé.'
            ], Response::HTTP_CONFLICT); // 409
        }

        return $this->json([
            'message' => 'Utilisateur créé',
            'id' => $user->getId(),
        ], Response::HTTP_CREATED);
    }


    #[OA\Put(
        path: '/api/users/{id}',
        operationId: 'updateUser',
        summary: 'Modifier son compte',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'username',
                        type: 'string'
                    ),
                    new OA\Property(
                        property: 'pass',
                        type: 'string',
                        format: 'password'
                    )
                ]
            )
        ),
        tags: ['Users'],
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
                description: 'Utilisateur modifié',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Utilisateur modifié'
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable')
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        PasswordHasherFactoryInterface $passwordHasher
    ): JsonResponse {
        // On ne peut modifier que son propre compte
        try {
            $this->denyAccessUnlessOwner($user);
        } catch (AccessDeniedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }

        if (isset($data['pass'])) {
            $user->setPass(
                $passwordHasher
                    ->getPasswordHasher($user)
                    ->hash($data['pass'])
            );
        }

        $em->flush();

        return $this->json([
            'message' => 'Utilisateur modifié'
        ]);
    }


    #[OA\Delete(
        path: '/api/users/{id}',
        operationId: 'deleteUser',
        summary: 'Supprimer son compte',
        security: [['Bearer' => []]],
        tags: ['Users'],
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
                description: 'Utilisateur supprimé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Utilisateur supprimé'
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable')
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        User $user,
        EntityManagerInterface $em
    ): JsonResponse {
        // On ne peut supprimer que son propre compte
        try {
            $this->denyAccessUnlessOwner($user);
        } catch (AccessDeniedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }

        $em->remove($user);
        $em->flush();

        return $this->json([
            'message' => 'Utilisateur supprimé'
        ]);
    }
}

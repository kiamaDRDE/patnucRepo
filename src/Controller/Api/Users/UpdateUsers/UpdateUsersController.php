<?php

namespace App\Controller\Api\Users\UpdateUsers;

use App\Entity\Roles\Roles;
use App\Repository\Users\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UpdateUsersController extends AbstractController
{
    #[Route('/api/user/{id}/update', name: 'api_user_update', methods: ["PATCH"])]
    #[OA\Patch(
        path: '/api/user/{id}/update',
        summary: 'Update a user.',
        tags: ['Users'],
        description: "Allows updating an existing user by their ID.",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The user ID.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'email', type: 'string', example: 'example@example.com'),
                        new OA\Property(property: 'password', type: 'string', example: 'securepassword'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER']),
                        new OA\Property(property: 'firstname', type: 'string', example: 'John'),
                        new OA\Property(property: 'lastname', type: 'string', example: 'Doe'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User successfully updated.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'User has been successfully updated.')
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Authentication required.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'You must be logged in.')
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized action.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'You are not authorized to update this user.')
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'User not found.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'User not found.')
                        ]
                    )
                )
            )
        ]
    )]
    public function updateUser(
        UsersRepository $userRepository,
        SerializerInterface $serializer,
        $id,
        EntityManagerInterface $entityManager,
        Request $request,
        UserPasswordHasherInterface $passwordHasher  // Utilisation de UserPasswordHasherInterface ici
    ): JsonResponse {
        $userItem = $userRepository->find($id);
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(
                ['message' => 'You must be logged in.'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(
                ['message' => 'You are not authorized to update users.'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        if (!$userItem) {
            return new JsonResponse(
                ['message' => 'User not found.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);
        
        // Mise à jour de l'email
        if (isset($data['email'])) {
            $userItem->setEmail($data['email']);
        }

        // Mise à jour du mot de passe
        if (isset($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($userItem, $data['password']);  // Utilisation de hashPassword()
            $userItem->setPassword($hashedPassword);
        }

        if (isset($data['roles'])) {
            $roles = $data['roles']; // Un tableau des noms de rôles
        
            // Récupérer les entités Roles correspondantes aux noms donnés
            foreach ($roles as $roleName) {
                $role = $entityManager->getRepository(Roles::class)->findOneBy(['roleName' => $roleName]);
                if ($role) {
                    // Ajouter chaque rôle existant à la collection
                    $userItem->addRole($role);
                }
            }
        }
        

        // Mise à jour du prénom
        if (isset($data['firstname'])) {
            $userItem->setFirstname($data['firstname']);
        }

        // Mise à jour du nom
        if (isset($data['lastname'])) {
            $userItem->setLastname($data['lastname']);
        }

        // Mise à jour de la date 'updatedAt'
        $userItem->setUpdatedAt(new \DateTimeImmutable());

        // Sauvegarde des modifications
        $entityManager->persist($userItem);
        $entityManager->flush();

        return new JsonResponse(
            ['message' => 'User has been successfully updated.'],
            Response::HTTP_OK
        );
    }
}

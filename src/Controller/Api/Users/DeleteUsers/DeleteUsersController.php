<?php

namespace App\Controller\Api\Users\DeleteUsers;

use App\Entity\Users\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

final class DeleteUsersController extends AbstractController{
    #[Route('/api/user/{id}/delete', name: 'api_user_delete', methods: ["DELETE"])]
    #[OA\Delete(
        path: '/api/user/{id}/delete',
        summary: 'Delete a user by ID.',
        tags: ['Users'],
        description: 'Allows administrators to delete a specific user by ID.',
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The ID of the user to delete.',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User deleted successfully.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'User deleted successfully.')
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
                description: 'Authorization error.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'You are not authorized to delete users.')
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
    public function deleteUser(
        $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Récupérer l'utilisateur à supprimer
        $user = $entityManager->getRepository(Users::class)->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur est connecté
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(
                ['message' => 'You must be logged in.'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        // Vérifier si l'utilisateur connecté a le rôle ADMIN ou SUPER_ADMIN
        if (!in_array('ROLE_ADMIN', $currentUser->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
            return new JsonResponse(
                ['message' => 'You are not authorized to delete users.'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        // Suppression de l'utilisateur
        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'User deleted successfully'], JsonResponse::HTTP_OK);
    }
}

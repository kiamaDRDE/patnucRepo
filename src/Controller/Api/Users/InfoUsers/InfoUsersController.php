<?php

namespace App\Controller\Api\Users\InfoUsers;

use App\Entity\Users\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

final class InfoUsersController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/userInfo', name: 'api_user_info', methods: ["GET"])]
    #[OA\Get(
        description: 'Retrieve the authenticated user\'s information (accessible by users with ROLE_ADMIN or ROLE_SUPER_ADMIN).',
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User information retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', description: 'User ID'),
                        new OA\Property(property: 'firstName', type: 'string', description: 'User first name'),
                        new OA\Property(property: 'lastName', type: 'string', description: 'User last name'),
                        new OA\Property(property: 'email', type: 'string', description: 'User email address'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', description: 'Account creation date'),
                        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', description: 'Last update date'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', description: 'User roles'))
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User not found')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied for users without ROLE_ADMIN or ROLE_SUPER_ADMIN',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'You do not have permission to access this resource')
                    ]
                )
            )
        ]
    )]
    public function getUserInfo(): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        // Vérifier si l'utilisateur connecté a un rôle admin ou super admin
        if (!in_array('ROLE_ADMIN', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            throw new AccessDeniedHttpException('You do not have permission to access this resource');
        }

        // Récupérer les informations de l'utilisateur connecté
        $data = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'createdAt' => $user->getCreatedAt(),
            'updatedAt' => $user->getUpdatedAt(),
            'roles' => $user->getRoles(),
        ];

        return $this->json($data);
    }

    #[Route('/api/user/{id}', name: 'api_user_get', methods: ["GET"])]
    #[OA\Get(
        description: 'Retrieve information of a specific user by their ID. Accessible by users with ROLE_ADMIN or ROLE_SUPER_ADMIN.',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'The ID of the user to retrieve information for.',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User information retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', description: 'User ID'),
                        new OA\Property(property: 'firstName', type: 'string', description: 'User first name'),
                        new OA\Property(property: 'lastName', type: 'string', description: 'User last name'),
                        new OA\Property(property: 'email', type: 'string', description: 'User email address'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', description: 'Account creation date'),
                        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', description: 'Last update date'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', description: 'User roles'))
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User not found')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied for users without ROLE_ADMIN or ROLE_SUPER_ADMIN',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'You do not have permission to access this resource')
                    ]
                )
            )
        ]
    )]
    public function OneUser(int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(Users::class)->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        // Vérifier si l'utilisateur connecté a un rôle admin ou super admin
        $currentUser = $this->security->getUser();
        if (!$currentUser || (!in_array('ROLE_ADMIN', $currentUser->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()))) {
            throw new AccessDeniedHttpException('You do not have permission to access this resource');
        }

        // Récupérer les informations de l'utilisateur selon l'ID
        $data = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'createdAt' => $user->getCreatedAt(),
            'updatedAt' => $user->getUpdatedAt(),
            'roles' => $user->getRoles(),
        ];

        return $this->json($data);
    }
}

<?php

namespace App\Controller\Api\Users\ListUsers;

use App\Repository\Users\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

final class ListUsersController extends AbstractController
{
    #[Route('/api/list/users', name: 'api_list_users')]
    #[OA\Get(
        description: 'Retrieve a list of all users. Accessible only by authenticated users with ROLE_ADMIN.',
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A list of users',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', description: 'User ID'),
                            new OA\Property(property: 'firstName', type: 'string', description: 'User first name'),
                            new OA\Property(property: 'lastName', type: 'string', description: 'User last name'),
                            new OA\Property(property: 'email', type: 'string', description: 'User email address'),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'User not authenticated',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User not authenticated')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied - user does not have the required role',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Access denied')
                    ]
                )
            )
        ]
    )]
    public function GetUsers(UsersRepository $userRepository): Response
    {
        // Check if the user is authenticated
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED); // Return a JSON response
        }

        // Check if the user has ROLE_ADMIN
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN); // Return forbidden response
        }

        // Get all users from the repository
        $users = $userRepository->findAll();

        // Return the list of users as a JSON response
        return $this->json($users);
    }
}

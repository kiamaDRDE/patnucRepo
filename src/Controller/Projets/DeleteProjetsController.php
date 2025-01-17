<?php

namespace App\Controller\Projets;

use App\Entity\Projets\Projets;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteProjetsController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/delete/projects/{id}', name: 'api_delete_projects', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/delete/projects/{id}',
        summary: 'Delete a project',
        description: 'Delete a specific project by its ID. Only the project creator or users with specific roles can perform this action.',
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The ID of the project to delete',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Project deleted successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'User not authenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User not authenticated')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied or not allowed to delete the project',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'You are not allowed to delete this project')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Project not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Project not found')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Failed to delete project: [error details]')
                    ]
                )
            )
        ]
    )]
    public function deleteProject(int $id): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            return $this->json(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if the user has ROLE_ADMIN, ROLE_SUPER_ADMIN, or ROLE_USER
        if (!in_array('ROLE_ADMIN', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_USER', $user->getRoles())) {
            return $this->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Find the project to delete
        $project = $this->entityManager->getRepository(Projets::class)->find($id);

        if (!$project) {
            return $this->json(['message' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the logged-in user is the project creator (optional logic)
        if ($project->getUsers() !== $user && !in_array('ROLE_ADMIN', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'You are not allowed to delete this project'], Response::HTTP_FORBIDDEN);
        }

        // Remove the project
        try {
            $this->entityManager->remove($project);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete project: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['message' => 'Project deleted successfully'], Response::HTTP_OK);
    }
}

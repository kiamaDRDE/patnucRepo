<?php

namespace App\Controller\Api\Taches;

use App\Repository\Taches\TachesRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DeleteTachesController extends AbstractController
{
    #[Route('/api/taches/{id}/delete', name: 'delete_tache', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/taches/{id}/delete',
        tags: ['Taches'],
        summary: 'Delete a specific task by ID',
        description: 'This endpoint allows an admin to delete a task identified by its ID.',
        security: [['bearerAuth' => []]], // Require bearer token
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The ID of the task to delete',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Tache deleted successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Task not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Tache with ID 1 not found')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Authentication required',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Unauthorized')
                    ]
                )
            )
        ]
    )]
    public function deleteTache(
        int $id,
        EntityManagerInterface $em,
        TachesRepository $tachesRepository
    ): JsonResponse {
        $task = $tachesRepository->find($id);

        if (!$task) {
            return new JsonResponse(['error' => "Tache with ID $id not found"], 404);
        }

        $em->remove($task);
        $em->flush();

        return new JsonResponse(['message' => 'Tache deleted successfully']);
    }
}

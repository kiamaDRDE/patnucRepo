<?php

namespace App\Controller\Api\Taches;

use App\Repository\Taches\TachesRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


final class GetTachesController extends AbstractController
{
    #[Route('/api/taches/{id}', name: 'get_tache', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/taches/{id}',
        tags: ['Taches'],
        summary: 'Retrieve a specific task by ID',
        description: 'This endpoint allows an admin to retrieve the details of a specific task identified by its ID.',
        security: [['bearerAuth' => []]], // Require bearer token
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'The ID of the task to retrieve',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nameTache', type: 'string', example: 'Task A'),
                        new OA\Property(property: 'dateDebut', type: 'string', format: 'date', example: '2025-01-01'),
                        new OA\Property(property: 'dateFin', type: 'string', format: 'date', example: '2025-12-31'),
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
    public function getTache(int $id, TachesRepository $tachesRepository): JsonResponse
    {
        $task = $tachesRepository->find($id);

        if (!$task) {
            return new JsonResponse(['error' => "Tache with ID $id not found"], 404);
        }

        return new JsonResponse([
            'id' => $task->getId(),
            'nameTache' => $task->getNameTache(),
            'dateDebut' => $task->getDateDebut()?->format('Y-m-d'),
            'dateFin' => $task->getDateFin()?->format('Y-m-d')
        ]);
    }
}

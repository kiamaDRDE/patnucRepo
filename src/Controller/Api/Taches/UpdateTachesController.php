<?php

namespace App\Controller\Api\Taches;

use App\Repository\Taches\TachesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

final class UpdateTachesController extends AbstractController
{
    #[Route('/api/taches/{id}/update', name: 'update_tache', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        description: 'Update a task by ID',
        tags: ['Taches'],
        path: '/api/taches/{id}/update',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID of the task to update',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: 'Task details to update',
            content: new OA\JsonContent(
                required: ['nameTache'],
                properties: [
                    new OA\Property(property: 'nameTache', type: 'string', description: 'Name of the task'),
                    new OA\Property(property: 'dateDebut', type: 'string', format: 'date', description: 'Start date of the task'),
                    new OA\Property(property: 'dateFin', type: 'string', format: 'date', description: 'End date of the task')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Tache updated successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Validation failed')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Task not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Tache with ID 123 not found')
                    ]
                )
            )
        ]
    )]
    public function updateTache(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TachesRepository $tachesRepository
    ): JsonResponse {
        $task = $tachesRepository->find($id);

        if (!$task) {
            return new JsonResponse(['error' => "Tache with ID $id not found"], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nameTache'])) {
            $task->setNameTache($data['nameTache']);
        }
        if (isset($data['dateDebut'])) {
            $task->setDateDebut(new \DateTime($data['dateDebut']));
        }
        if (isset($data['dateFin'])) {
            $task->setDateFin(new \DateTime($data['dateFin']));
        }

        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string)$errors], 400);
        }

        $em->flush();

        return new JsonResponse(['message' => 'Tache updated successfully']);
    }
}


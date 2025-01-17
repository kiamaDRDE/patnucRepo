<?php

namespace App\Controller\Api\Taches;

use App\Entity\Taches\Taches;
use App\Repository\Projets\ProjetsRepository;
use App\Repository\Taches\TachesRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateTachesController extends AbstractController
{
    #[Route('/api/taches/create', name: 'create_taches', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        path: '/api/taches/create',
        tags: ['Taches'],
        summary: 'Create tasks and assign them to all projects',
        description: 'This endpoint allows an admin to create multiple tasks and automatically assign them to all existing projects.',
        security: [['bearerAuth' => []]], // Require bearer token
        requestBody: new OA\RequestBody(
            description: 'Tasks to create and assign to projects',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'taches',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'nameTache', type: 'string', example: 'Task A'),
                                new OA\Property(property: 'dateDebut', type: 'string', format: 'date', example: '2025-01-01'),
                                new OA\Property(property: 'dateFin', type: 'string', format: 'date', example: '2025-12-31'),
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tasks created and assigned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Taches created and assigned to all projects successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Invalid input or validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Invalid input data')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - No projects found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'No projects found')
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
    public function createTaches(
        Request $request,
        EntityManagerInterface $em,
        ProjetsRepository $projetsRepository,
        TachesRepository $tachesRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
    
        if (!$data || !isset($data['taches']) || !is_array($data['taches'])) {
            return new JsonResponse(['error' => 'Invalid input data'], 400);
        }
    
        // Récupérer tous les projets existants
        $allProjects = $projetsRepository->findAll();
    
        if (empty($allProjects)) {
            return new JsonResponse(['error' => 'No projects found'], 404);
        }
    
        $taches = [];
        foreach ($data['taches'] as $taskData) {
            if (!isset($taskData['nameTache']) || empty($taskData['nameTache'])) {
                return new JsonResponse(['error' => 'Task name is required'], 400);
            }
    
            // Vérifier si le nom de la tâche existe déjà
            if ($tachesRepository->findOneBy(['nameTache' => $taskData['nameTache']])) {
                return new JsonResponse(['error' => "Task with name '{$taskData['nameTache']}' already exists"], 400);
            }
    
            $task = new Taches();
            $task->setNameTache($taskData['nameTache']);
    
            if (isset($taskData['dateDebut'])) {
                $task->setDateDebut(new \DateTime($taskData['dateDebut']));
            }
    
            if (isset($taskData['dateFin'])) {
                $task->setDateFin(new \DateTime($taskData['dateFin']));
            }
    
            // Associer la tâche à tous les projets
            foreach ($allProjects as $project) {
                $project->addTach($task);
            }
    
            // Valider la tâche
            $errors = $validator->validate($task);
            if (count($errors) > 0) {
                return new JsonResponse(['error' => (string)$errors], 400);
            }
    
            $em->persist($task);
            $taches[] = $task;
        }
    
        $em->flush();
    
        return new JsonResponse(['message' => 'Taches created and assigned to all projects successfully'], 201);
    }
}

<?php

namespace App\Controller\Projets;

use App\Entity\Projets\Projets;
use App\Entity\Taches\Taches;
use App\Repository\Projets\ProjetsRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetProjetsController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/list/projects', name: 'api_list_projects', methods: ['GET'])] 
    #[OA\Get(
        path: '/api/list/projects',
        summary: 'Retrieve the list of projects for the authenticated user',
        description: 'This endpoint returns a list of projects associated with the authenticated user. It requires the user to be authenticated and authorized.',
        tags: ['Projects'],
        security: [['bearerAuth' => []]], // Specifies that this endpoint requires a bearer token
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of projects retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'projects',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Project A'),
                                    new OA\Property(property: 'dateDebut', type: 'string', format: 'date', example: '2025-01-01'),
                                    new OA\Property(property: 'dateFin', type: 'string', format: 'date', example: '2025-12-31'),
                                    new OA\Property(
                                        property: 'tasks',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                                new OA\Property(property: 'name', type: 'string', example: 'Task A'),
                                                new OA\Property(property: 'dateDebut', type: 'string', format: 'date', example: '2025-01-01'),
                                                new OA\Property(property: 'dateFin', type: 'string', format: 'date', example: '2025-02-01')
                                            ]
                                        )
                                    )
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - The user is not authenticated or lacks proper authorization',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Unauthorized')
                    ]
                )
            )
        ]
    )]
    public function __invoke(): Response
    {
        $user = $this->security->getUser();

        if ($user) {
            // Récupérer les projets associés à l'utilisateur
            $projets = $this->entityManager->getRepository(Projets::class)->findBy(['users' => $user]);

            // Récupérer toutes les tâches existantes
            $allTaches = $this->entityManager->getRepository(Taches::class)->findAll();

            // Mapper les projets pour la réponse JSON
            $projetsData = array_map(function(Projets $projet) use ($allTaches) {
                // Mapper toutes les tâches (qu'elles soient associées ou non au projet)
                $tasks = array_map(function(Taches $tache) {
                    return [
                        'id' => $tache->getId(),
                        'name' => $tache->getNameTache(),
                        'dateDebut' => $tache->getDateDebut()?->format('Y-m-d'),
                        'dateFin' => $tache->getDateFin()?->format('Y-m-d'),
                    ];
                }, $allTaches);

                return [
                    'id' => $projet->getId(),
                    'name' => $projet->getName(),
                    'dateDebut' => $projet->getDateDebut()->format('Y-m-d'),
                    'dateFin' => $projet->getDateFin()->format('Y-m-d'),
                    'tasks' => $tasks, // Toutes les tâches de la base de données
                ];
            }, $projets);

            // Retourner la liste des projets avec les tâches
            return $this->json(['projects' => $projetsData]);
        }

        // Retourner une réponse d'erreur si l'utilisateur n'est pas authentifié
        return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }


    #[Route('/api/project/{id}', name: 'get_project', methods: ['GET'])]
    public function getProject(int $id, ProjetsRepository $projetsRepository): JsonResponse
    {
        $projet = $projetsRepository->find($id);

        if (!$projet) {
            return new JsonResponse(['error' => 'Project not found'], 404);
        }

        $taches = array_map(fn(Taches $tache) => [
            'id' => $tache->getId(),
            'nameTache' => $tache->getNameTache(),
            'dateDebut' => $tache->getDateDebut()?->format('Y-m-d'),
            'dateFin' => $tache->getDateFin()?->format('Y-m-d'),
        ], $projet->getTaches()->toArray());

        return new JsonResponse([
            'id' => $projet->getId(),
            'name' => $projet->getName(),
            'dateDebut' => $projet->getDateDebut()?->format('Y-m-d'),
            'dateFin' => $projet->getDateFin()?->format('Y-m-d'),
            'taches' => $taches,
        ]);
    }
}

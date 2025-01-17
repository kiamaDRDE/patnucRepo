<?php

namespace App\Controller\Projets;

use App\Entity\Projets\Projets;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Projects")]
final class CreateProjetsController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/create/projects', name: 'api_create_projects', methods: ['POST'])]
    #[OA\Post(
        summary: "Create new projects",
        description: "Allows authenticated users (with appropriate roles) to create one or multiple projects.",
        requestBody: new OA\RequestBody(
            description: "Payload containing the project data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "projects",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "name", type: "string", example: "Project Name"),
                                new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2023-01-01"),
                                new OA\Property(property: "dateFin", type: "string", format: "date", example: "2023-12-31"),
                            ]
                        )
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Projects created successfully.",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Projects created successfully"),
                new OA\Property(
                    property: "projects",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "name", type: "string", example: "Project Name"),
                            new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2023-01-01"),
                            new OA\Property(property: "dateFin", type: "string", format: "date", example: "2023-12-31"),
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: "User not authenticated.")]
    #[OA\Response(response: 403, description: "Access denied.")]
    #[OA\Response(response: 400, description: "Invalid or missing fields in the request.")]
    #[OA\Response(response: 409, description: "A project with the same name already exists for the user.")]
    #[OA\Response(response: 500, description: "Server error while saving the project.")]
    public function createProjects(Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            return $this->json(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérification des rôles de l'utilisateur
        if (!in_array('ROLE_ADMIN', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_USER', $user->getRoles())) {
            return $this->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Vérification des données
        if (empty($data['projects'])) {
            return $this->json(['error' => 'Missing required fields: projects'], Response::HTTP_BAD_REQUEST);
        }

        // Récupération de toutes les tâches existantes
        $tachesRepository = $this->entityManager->getRepository(\App\Entity\Taches\Taches::class);
        $allTaches = $tachesRepository->findAll();

        $projects = [];
        foreach ($data['projects'] as $projectData) {
            if (empty($projectData['name']) || empty($projectData['dateDebut']) || empty($projectData['dateFin'])) {
                return $this->json(['error' => 'Missing required fields in project'], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si un projet avec le même nom existe déjà
            $existingProject = $this->entityManager->getRepository(Projets::class)
                ->findOneBy(['name' => $projectData['name'], 'users' => $user]);

            if ($existingProject) {
                return $this->json([
                    'error' => 'A project with the same name already exists',
                    'existing_project_name' => $existingProject->getName()
                ], Response::HTTP_CONFLICT);
            }

            // Création du nouveau projet
            $project = new Projets();
            $project->setName($projectData['name'])
                ->setDateDebut(new \DateTime($projectData['dateDebut']))
                ->setDateFin(new \DateTime($projectData['dateFin']))
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
                ->setUsers($user);

            // Associer toutes les tâches existantes à ce projet
            foreach ($allTaches as $tache) {
                $project->addTach($tache);
            }

            $this->entityManager->persist($project);
            $projects[] = $project;
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to save project: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Sérialisation manuelle des données pour éviter les références circulaires
        $response = array_map(function (Projets $project) use ($allTaches){
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'dateDebut' => $project->getDateDebut()?->format('Y-m-d'),
            'dateFin' => $project->getDateFin()?->format('Y-m-d'),
            
            // Exclure explicitement les relations si elles ne sont pas nécessaires
        ];
    }, $projects);

        return $this->json(['message' => 'Projects created successfully', 'projects' => $response], Response::HTTP_CREATED);
    }

}
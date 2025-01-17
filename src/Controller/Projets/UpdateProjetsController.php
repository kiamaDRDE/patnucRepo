<?php

// src/Controller/Projets/UpdateProjetsController.php

namespace App\Controller\Projets;

use App\Entity\Projets\Projets;
use App\Entity\Users\Users;
use App\Repository\Projets\ProjetsRepository;
use App\Repository\Users\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[OA\Tag(name: "Projects")]
final class UpdateProjetsController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(Security $security, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    #[Route('/api/update/projects/{id}', name: 'api_update_projects', methods: ['PATCH'])]
    #[OA\Patch(
        summary: "Update an existing project",
        description: "Allows updating specific fields of a project, such as name, start date, end date, or associated user.",
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID of the project to update",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Fields to update in the project",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Updated Project Name"),
                    new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2023-01-01"),
                    new OA\Property(property: "dateFin", type: "string", format: "date", example: "2023-12-31"),
                    new OA\Property(property: "user", type: "integer", example: 3, description: "ID of the associated user")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Project updated successfully",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "name", type: "string", example: "Updated Project Name"),
                new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2023-01-01"),
                new OA\Property(property: "dateFin", type: "string", format: "date", example: "2023-12-31"),
                new OA\Property(property: "updatedAt", type: "string", format: "datetime", example: "2023-10-25 14:30:00"),
                new OA\Property(
                    property: "user",
                    type: "object",
                    nullable: true,
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 3),
                        new OA\Property(property: "name", type: "string", example: "User Name")
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Project not found")]
    public function patchProjet(
        int $id, 
        Request $request, 
        ProjetsRepository $projetsRepository, 
        UsersRepository $usersRepository
    ): JsonResponse {
        // Récupérer le projet par ID
        $projet = $projetsRepository->find($id);
        if (!$projet) {
            throw new NotFoundHttpException('Projet non trouvé');
        }

        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);

        // Mettre à jour les propriétés du projet si elles sont présentes dans les données
        if (isset($data['name'])) {
            $projet->setName($data['name']);
        }
        if (isset($data['dateDebut'])) {
            $projet->setDateDebut(new \DateTime($data['dateDebut']));
        }
        if (isset($data['dateFin'])) {
            $projet->setDateFin(new \DateTime($data['dateFin']));
        }
        if (isset($data['user'])) {
            // Récupérer l'utilisateur par ID
            $user = $usersRepository->find($data['user']);
            if ($user) {
                $projet->setUsers($user);
            }
        }

        // Mettre à jour la date de mise à jour du projet
        $projet->setUpdatedAt(new \DateTimeImmutable());

        // Enregistrer les modifications dans la base de données
        $this->entityManager->flush();

        // Retourner le projet mis à jour en réponse JSON
        return new JsonResponse([
            'id' => $projet->getId(),
            'name' => $projet->getName(),
            'dateDebut' => $projet->getDateDebut()->format('Y-m-d'),
            'dateFin' => $projet->getDateFin()->format('Y-m-d'),
            'updatedAt' => $projet->getUpdatedAt()->format('Y-m-d H:i:s'),
            'user' => $projet->getUsers() ? [
                'id' => $projet->getUsers()->getId(),
                'name' => $projet->getUsers()->getName()
            ] : null
        ]);
    }
}
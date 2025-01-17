<?php

namespace App\Controller\Api\Roles;

use App\Repository\Roles\RolesRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;


final class GetRolesController extends AbstractController
{
    #[Route('/api/list/roles', name: 'get_all_roles', methods: ['GET'])]
    #[OA\Get(
        path: "/api/list/roles",
        tags: ['Roles'],
        summary: "Récupérer la liste des rôles",
        security: [["bearerAuth" => []]], 
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des rôles récupérée avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Roles retrieved successfully"
                        ),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "roleName", type: "string", example: "ROLE_ADMIN"),
                                    new OA\Property(
                                        property: "accesses",
                                        type: "array",
                                        items: new OA\Items(type: "string"),
                                        example: ["VIEW_DASHBOARD", "EDIT_USER"]
                                    )
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: "Accès refusé")
        ]
    )]
    public function getAllRoles(RolesRepository $rolesRepository): JsonResponse
    {
        // Vérification des rôles avant de récupérer les données
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        // Récupérer tous les rôles avec leurs accès associés
        $roles = $rolesRepository->findAll();

        // Formater les données pour la réponse
        $data = array_map(function ($role) {
            return [
                'id' => $role->getId(),
                'roleName' => $role->getRoleName(),
                'accesses' => array_map(
                    fn($access) => $access->getAccessName(),
                    $role->getAccesses()->toArray()
                ),
            ];
        }, $roles);

        return new JsonResponse([
            'message' => 'Roles retrieved successfully',
            'data' => $data,
        ], 200);
    }
}

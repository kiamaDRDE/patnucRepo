<?php

namespace App\Controller\Api\Roles;

use App\Entity\Access\Access;
use App\Entity\Roles\Roles;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;


final class UpdateRolesController extends AbstractController
{
    #[Route('/api/roles/{id}/update', name: 'update_role', methods: ['PATCH'])]
    #[OA\Patch(
        path: "/api/roles/{id}/update",
        summary: "Mettre à jour un rôle",
        tags: ['Roles'],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "ID du rôle à mettre à jour"
            )
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "roleName", type: "string", example: "ROLE_MANAGER"),
                    new OA\Property(
                        property: "accesses",
                        type: "array",
                        items: new OA\Items(type: "string"),
                        example: ["VIEW_DASHBOARD", "EDIT_USER"]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Rôle mis à jour avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Role updated successfully"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "roleName", type: "string", example: "ROLE_MANAGER"),
                                new OA\Property(
                                    property: "accesses",
                                    type: "array",
                                    items: new OA\Items(type: "string"),
                                    example: ["VIEW_DASHBOARD", "EDIT_USER"]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 403, description: "Accès refusé"),
            new OA\Response(response: 404, description: "Rôle non trouvé")
        ]
    )]
    public function updateRole(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérification des rôles avant modification
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $roleRepository = $entityManager->getRepository(Roles::class);
        $accessRepository = $entityManager->getRepository(Access::class);

        $role = $roleRepository->find($id);

        if (!$role) {
            return new JsonResponse(['error' => 'Role not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['roleName']) && !empty($data['roleName'])) {
            $role->setRoleName($data['roleName']);
        }

        if (isset($data['accesses']) && is_array($data['accesses'])) {
            $validAccesses = [];
            $invalidAccesses = [];

            foreach ($data['accesses'] as $accessName) {
                $access = $accessRepository->findOneBy(['AccessName' => $accessName]);
                if ($access) {
                    $validAccesses[] = $access;
                } else {
                    $invalidAccesses[] = $accessName;
                }
            }

            if (!empty($invalidAccesses)) {
                return new JsonResponse([
                    'error' => 'The following accesses do not exist: ' . implode(', ', $invalidAccesses)
                ], 400);
            }

            // 🔥 Supprime les anciens accès avant d'ajouter les nouveaux
            foreach ($role->getAccesses() as $access) {
                $role->removeAccess($access);
            }

            foreach ($validAccesses as $access) {
                $role->addAccess($access);
            }
        }

        $role->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Role updated successfully',
            'data' => [
                'roleName' => $role->getRoleName(),
                'accesses' => array_map(fn($access) => $access->getAccessName(), $role->getAccesses()->toArray()),
            ],
        ], 200);
    }
}

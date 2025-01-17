<?php

namespace App\Controller\Api\Roles;

use App\Entity\Roles\Roles;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


final class DeleteRolesController extends AbstractController
{
    #[Route('/api/roles/delete/{id}', name: 'delete_role', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/roles/delete/{id}",
        tags: ['Roles'],
        summary: "Supprimer un rôle",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Rôle supprimé"),
            new OA\Response(response: 404, description: "Rôle introuvable"),
            new OA\Response(response: 403, description: "Accès refusé")
        ]
    )]
    public function deleteRole(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $roleRepository = $entityManager->getRepository(Roles::class);
        $role = $roleRepository->find($id);

        if (!$role) {
            return new JsonResponse(['error' => 'Role not found'], 404);
        }

        $entityManager->remove($role);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Role deleted successfully'], 200);
    }
}

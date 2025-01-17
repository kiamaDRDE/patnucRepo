<?php

namespace App\Controller\Api\Roles;

use App\Entity\Access\Access;
use App\Entity\Roles\Roles;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


final class CreateRolesController extends AbstractController
{
    #[Route('/api/roles/create-or-update', name: 'create_or_update_role', methods: ['POST'])]
    #[OA\Post(
        path: "/api/roles/create-or-update",
        tags: ['Roles'],
        summary: "Créer ou mettre à jour un rôle",
        security: [["bearerAuth" => []]],
        
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "roleName", type: "string"),
                    new OA\Property(property: "accesses", type: "array", items: new OA\Items(type: "string"))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Rôle créé ou mis à jour"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 403, description: "Accès refusé")
        ]
    )]
    public function createOrUpdateRole(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['roleName']) || empty($data['roleName'])) {
            return new JsonResponse(['error' => 'Role name is required'], 400);
        }

        $roleName = $data['roleName'];
        $accessNames = $data['accesses'] ?? [];

        if (empty($accessNames)) {
            return new JsonResponse(['error' => 'At least one access is required'], 400);
        }

        $roleRepository = $entityManager->getRepository(Roles::class);
        $accessRepository = $entityManager->getRepository(Access::class);

        $role = $roleRepository->findOneBy(['roleName' => $roleName]) ?? new Roles();
        $role->setRoleName($roleName);
        $role->setUpdatedAt(new \DateTimeImmutable());

        if (!$role->getId()) {
            $role->setCreatedAt(new \DateTimeImmutable());
        }

        $validAccesses = [];
        foreach ($accessNames as $accessName) {
            $access = $accessRepository->findOneBy(['AccessName' => $accessName]);
            if ($access) {
                $validAccesses[] = $access;
            }
        }

        foreach ($role->getAccesses() as $access) {
            $role->removeAccess($access);
        }

        foreach ($validAccesses as $access) {
            $role->addAccess($access);
        }

        $entityManager->persist($role);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Role created or updated successfully',
            'data' => [
                'roleName' => $role->getRoleName(),
                'accesses' => array_map(fn($access) => $access->getAccessName(), $role->getAccesses()->toArray()),
            ],
        ], 201);
    }
}

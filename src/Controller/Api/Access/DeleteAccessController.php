<?php

namespace App\Controller\Api\Access;

use App\Repository\Access\AccessRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteAccessController extends AbstractController
{
    #[Route('/api/access/delete', name: 'delete_access', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/access/delete",
        tags: ['Access'],
        summary: "Supprimer un ou plusieurs accès",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "accessName", type: "string", example: "VIEW_REPORTS")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Accès supprimés avec succès"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 403, description: "Accès refusé")
        ]
    )]
    public function deleteAccess(
        Request $request,
        EntityManagerInterface $entityManager,
        AccessRepository $accessRepository
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data)) {
            return new JsonResponse(['error' => 'A non-empty array of access identifiers is required'], 400);
        }

        $deletedAccesses = [];
        $notFoundAccesses = [];

        foreach ($data as $accessItem) {
            if (!isset($accessItem['id']) && !isset($accessItem['accessName'])) {
                return new JsonResponse([
                    'error' => 'Each item must have an "id" or "accessName" property'
                ], 400);
            }

            $access = null;

            if (isset($accessItem['id'])) {
                $access = $accessRepository->find($accessItem['id']);
            } elseif (isset($accessItem['accessName'])) {
                $access = $accessRepository->findOneBy(['accessName' => $accessItem['accessName']]);
            }

            if (!$access) {
                $notFoundAccesses[] = $accessItem;
                continue;
            }

            $entityManager->remove($access);
            $deletedAccesses[] = $accessItem;
        }

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Accesses processed successfully',
            'deletedAccesses' => $deletedAccesses,
            'notFoundAccesses' => $notFoundAccesses,
        ], 200);
    }

    #[Route('/api/access/delete/{id}', name: 'delete_access_by_id', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/access/delete/{id}",
        summary: "Supprimer un accès par ID",
        tags: ['Access'],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "ID de l'accès à supprimer"
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Accès supprimé avec succès"),
            new OA\Response(response: 404, description: "Accès non trouvé"),
            new OA\Response(response: 403, description: "Accès refusé")
        ]
    )]
    public function deleteAccessById(
        int $id,
        EntityManagerInterface $entityManager,
        AccessRepository $accessRepository
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $access = $accessRepository->find($id);

        if (!$access) {
            return new JsonResponse([
                'error' => sprintf('Access with ID %d not found', $id),
            ], 404);
        }

        $entityManager->remove($access);
        $entityManager->flush();

        return new JsonResponse([
            'message' => sprintf('Access with ID %d deleted successfully', $id),
        ], 200);
    }

}

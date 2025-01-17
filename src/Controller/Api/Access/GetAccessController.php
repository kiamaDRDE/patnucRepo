<?php

namespace App\Controller\Api\Access;

use App\Repository\Access\AccessRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetAccessController extends AbstractController
{
    #[Route('/api/get/access', name: 'get_all_accesses', methods: ['GET'])]
    #[OA\Get(
        path: "/api/get/access",
        tags: ['Access'],
        summary: "Récupérer tous les accès",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des accès récupérée avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Accesses retrieved successfully"
                        ),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "AccessName", type: "string", example: "VIEW_REPORTS")
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 403, description: "Accès refusé")
        ]
    )]
    public function getAllAccesses(AccessRepository $accessRepository): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $accesses = $accessRepository->findAll();

        $data = array_map(static function ($access) {
            return [
                'id' => $access->getId(),
                'AccessName' => $access->getAccessName(),
            ];
        }, $accesses);

        return new JsonResponse([
            'message' => 'Accesses retrieved successfully',
            'data' => $data,
        ], 200);
    }

    #[Route('/api/get/access/{id}', name: 'get_access_by_id', methods: ['GET'])]
    #[OA\Get(
        path: "/api/get/access/{id}",
        summary: "Récupérer un accès par ID",
        tags: ['Access'],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "ID de l'accès à récupérer"
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Accès récupéré avec succès"),
            new OA\Response(response: 404, description: "Accès non trouvé"),
            new OA\Response(response: 403, description: "Accès refusé")
        ]
    )]
    public function getAccessById(int $id, AccessRepository $accessRepository): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $access = $accessRepository->find($id);

        if (!$access) {
            return new JsonResponse(['error' => 'Access not found'], 404);
        }

        return new JsonResponse([
            'message' => 'Access retrieved successfully',
            'data' => [
                'id' => $access->getId(),
                'AccessName' => $access->getAccessName(),
            ],
        ], 200);
    }

}

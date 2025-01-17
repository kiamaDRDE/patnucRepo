<?php

namespace App\Controller\Api\Access;

use App\Repository\Access\AccessRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;


final class UpdateAccessController extends AbstractController
{
    #[Route('/api/access/update', name: 'update_access', methods: ['PATCH'])]
    #[OA\Patch(
        path: "/api/access/update",
        tags: ['Access'],
        summary: "Mettre à jour un accès",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "AccessName", type: "string", example: "EDIT_REPORTS")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Accès mis à jour avec succès"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 404, description: "Accès non trouvé"),
            new OA\Response(response: 403, description: "Accès refusé"),
            new OA\Response(response: 409, description: "Un autre accès avec le même nom existe déjà")
        ]
    )]
    public function updateAccess(
        Request $request,
        EntityManagerInterface $entityManager,
        AccessRepository $accessRepository
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['id']) || empty($data['id'])) {
            return new JsonResponse(['error' => 'The "id" field is required'], 400);
        }

        if (!isset($data['AccessName']) || empty($data['AccessName'])) {
            return new JsonResponse(['error' => 'The "AccessName" field is required'], 400);
        }

        $access = $accessRepository->find($data['id']);
        if (!$access) {
            return new JsonResponse(['error' => 'Access not found'], 404);
        }

        $existingAccess = $accessRepository->findOneBy(['AccessName' => $data['AccessName']]);
        if ($existingAccess && $existingAccess->getId() !== $access->getId()) {
            return new JsonResponse(['error' => 'Another access with the same name already exists'], 409);
        }

        $access->setAccessName($data['AccessName']);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Access updated successfully',
            'access' => [
                'id' => $access->getId(),
                'AccessName' => $access->getAccessName(),
            ],
        ], 200);
    }
}

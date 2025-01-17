<?php

namespace App\Controller\Api\Access;

use App\Entity\Access\Access;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;


final class CreateAccessController extends AbstractController
{
    #[Route('/api/access/create', name: 'create_access', methods: ['POST'])]
    #[OA\Post(
        path: "/api/access/create",
        tags: ['Access'],
        summary: "Créer un ou plusieurs accès",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: "accessName", type: "string", example: "VIEW_REPORTS")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Accès créés avec succès"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 403, description: "Accès refusé")
        ]
    )]
    public function createAccess(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data)) {
            return new JsonResponse(['error' => 'A non-empty array of access names is required'], 400);
        }

        $createdAccesses = [];
        foreach ($data as $accessItem) {
            if (!isset($accessItem['accessName']) || empty($accessItem['accessName'])) {
                return new JsonResponse([
                    'error' => 'Each access must have a non-empty "accessName" property'
                ], 400);
            }

            $access = new Access();
            $access->setAccessName($accessItem['accessName']);
            $entityManager->persist($access);
            $createdAccesses[] = $accessItem['accessName'];
        }

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Accesses created successfully',
            'createdAccesses' => $createdAccesses,
        ], 201);
    }
}

<?php

namespace App\Controller\Api\Users\Login;

use App\Entity\Users\Users; // Entité User, utilisée pour interagir avec la base de données
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Classe de base des contrôleurs Symfony
use Symfony\Component\HttpFoundation\JsonResponse; // Classe pour générer des réponses JSON
use Symfony\Component\HttpFoundation\Response; // Classe pour générer des réponses HTTP
use Symfony\Component\Routing\Attribute\Route; // Annotation pour définir les routes
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils; // Utilisé pour gérer les erreurs d'authentification
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

use OpenApi\Attributes as OA;

#[OA\Tag(name: "Login check")]

class LoginController extends AbstractController
{
    private $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    // Route pour afficher la page de connexion (GET)
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('api/login/index.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route('/api/login', name: 'api_login', methods: ["POST"])]
    #[OA\Post(
        path: "/api/login",
        summary: "Creates a user token.",
        tags: ['Login check'],
        description: "Creates a user token.",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "username", type: "string", example: 'user1@example.com'),
                    new OA\Property(property: "password", type: "string", example: 'user123')
                ],
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "JWT token returned.",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "email", type: "string"),
                        new OA\Property(property: "first_name", type: "string"),
                        new OA\Property(property: "last_name", type: "string"),
                        new OA\Property(property: "token", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Error 401: Invalid credentials or too many failed login attempts. Please try again in 3 minutes."),
            new OA\Response(response: 400, description: 'Error 400: Bad Request.'),
        ]
    )]
    public function ApiLogin(AuthenticationUtils $authenticationUtils): JsonResponse
    {
        // Vérifie si l'utilisateur est authentifié via Symfony
        $user = $this->getUser();

        if (!$user instanceof Users) {
            return new JsonResponse(['message' => 'User not authenticated'], 401);
        }

        // Génère le token JWT
        $token = $this->jwtManager->create($user);

        // Prépare les données de l'utilisateur
        $userData = [
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'token' => $token,  // Ajout du token dans la réponse
        ];

        return new JsonResponse($userData);
    }

    // Route pour la déconnexion (GET)
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // Symfony gère la déconnexion, on n'a donc rien à retourner ici.
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

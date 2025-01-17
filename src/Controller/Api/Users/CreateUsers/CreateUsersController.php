<?php

namespace App\Controller\Api\Users\CreateUsers;

use App\Entity\Roles\Roles;
use App\Entity\Users\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

final class CreateUsersController extends AbstractController
{
    private $security;
    private $entityManager;
    private $validator;
    private $userPasswordHasher;

    public function __construct(Security $security, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->userPasswordHasher = $userPasswordHasher; // Correction ici
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        description: 'Create a new user. Requires the first name, last name, email, and password. Optionally, the role can be provided.',
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'firstname', type: 'manu', description: 'User first name'),
                    new OA\Property(property: 'lastname', type: 'manu1', description: 'User last name'),
                    new OA\Property(property: 'email', type: 'manudouanla92@gmail.com', description: 'User email address'),
                    new OA\Property(property: 'password', type: '123456', description: 'User password'),
                    new OA\Property(property: 'role', type: 'array', items: new OA\Items(type: 'ROLE_USER'), description: 'User roles, defaults to ROLE_USER if not provided')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User created successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid data or validation errors',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Missing required fields')
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Email already in use',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Email already in use')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Failed to save user: database error')
                    ]
                )
            )
        ]
    )]
    public function register(Request $request): Response
    {
        // Decode the JSON input data
        $data = json_decode($request->getContent(), true);
    
        // Check if the JSON is valid
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }
    
        // Normalize keys to handle variations in casing (firstName, lastName, etc.)
        $data = array_change_key_case($data, CASE_LOWER);
    
        // Validate required fields
        if (empty($data['firstname']) || empty($data['lastname']) || empty($data['email']) || empty($data['password'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }
    
        // Create a new user object
        $user = new Users();
        $user->setFirstName($data['firstname'])
            ->setLastName($data['lastname'])
            ->setEmail($data['email'])
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());
    
        // Encode the password before saving
        $hashedPassword = $this->userPasswordHasher->hashPassword($user, $data['password']); // Utilisation de `hashPassword`
        $user->setPassword($hashedPassword);
    
        // Validate the user entity
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
    
        // Check if the email already exists
        $existingUser = $this->entityManager->getRepository(Users::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'Email already in use'], Response::HTTP_CONFLICT);
        }
    
        // Check for the role key in the request, use default role if not found
        $roles = isset($data['role']) ? (array) $data['role'] : ['ROLE_USER'];
    
        foreach ($roles as $roleName) {
            // Check if the role exists in the database
            $role = $this->entityManager->getRepository(Roles::class)->findOneBy(['roleName' => $roleName]);
            if (!$role) {
                return $this->json(['error' => "Role '$roleName' not found"], Response::HTTP_BAD_REQUEST);
            }
            $user->addRole($role);
        }
    
        // Save the new user
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to save user: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    
        return $this->json(['message' => 'User created successfully'], Response::HTTP_CREATED);
    }
}

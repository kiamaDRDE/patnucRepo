<?php

namespace App\Controller\Api\Users\PasswordReset;

use App\Entity\Token\Token;
use App\Entity\Users\Users;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


final class PasswordResetController extends AbstractController{
    #[Route('/api/password/request-reset', name: 'password_request_reset', methods: ['POST'])]
    #[OA\Post(
        tags: ['Password Reset'],
        summary: 'Request a password reset token',
        description: 'Generates a reset token, stores it in the database, and sends it to the user\'s email.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'email', type: 'string', description: 'User email', example: 'user@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reset token sent'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function requestReset(
        Request $request,
        EntityManagerInterface $entityManager,
        MailService $mailService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
    
        if (!$email) {
            return new JsonResponse(['message' => 'L\'email est requis'], 400);
        }
    
        // Vérifier si l'utilisateur existe
        $user = $entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }
    
        // Générer un token sécurisé
        $resetToken = bin2hex(random_bytes(16)); // Un token sécurisé
        $expiresAt = (new \DateTimeImmutable())->modify('+1 hour');
    
        // Sauvegarder le token en base de données
        $tokenEntity = new Token();
        $tokenEntity->setEmail($email);
        $tokenEntity->setToken($resetToken);
        $tokenEntity->setExpiresAt($expiresAt);
    
        $entityManager->persist($tokenEntity);
        $entityManager->flush();
    
        // Envoyer l'email avec le token
        $mailService->sendEmail(
            $email,
            'Demande de réinitialisation de mot de passe',
            "<p>Utilisez ce token pour réinitialiser votre mot de passe : <strong>{$resetToken}</strong></p>"
        );
    
        return new JsonResponse(['message' => 'Token de réinitialisation envoyé'], 200);
    }
    



    #[Route('/api/password/reset', name: 'password_reset', methods: ['POST'])]
    #[OA\Post(
        tags: ['Password Reset'],
        summary: 'Reset the user\'s password using a token',
        description: 'Allows a user to reset their password using the token sent to their email.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'token', type: 'string', description: 'Password reset token'),
                    new OA\Property(property: 'new_password', type: 'string', description: 'New password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password successfully reset'),
            new OA\Response(response: 400, description: 'Invalid token or token expired'),
        ]
    )]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        MailService $mailService,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
    
        $resetToken = $data['token'] ?? null;
        $newPassword = $data['new_password'] ?? null;
    
        if (!$resetToken || !$newPassword) {
            return new JsonResponse(['message' => 'Le token et le nouveau mot de passe sont requis'], 400);
        }
    
        // Vérifier le token dans la base de données
        $tokenEntity = $entityManager->getRepository(Token::class)->findOneBy(['token' => $resetToken]);
    
        if (!$tokenEntity) {
            return new JsonResponse(['message' => 'Token invalide'], 400);
        }
    
        // Vérifier si le token a expiré
        if ($tokenEntity->getExpiresAt() < new \DateTimeImmutable()) {
            return new JsonResponse(['message' => 'Token expiré'], 400);
        }
    
        // Trouver l'utilisateur par email
        $user = $entityManager->getRepository(Users::class)->findOneBy(['email' => $tokenEntity->getEmail()]);
    
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }
    
        // Hashage du nouveau mot de passe
        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
    
        // Supprimer le token après utilisation
        $entityManager->remove($tokenEntity);
        $entityManager->flush();
    
        // Envoi d'un email de confirmation (optionnel)
        $mailService->sendEmail(
            $user->getEmail(),
            'Mot de passe réinitialisé avec succès',
            '<p>Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>'
        );
    
        return new JsonResponse(['message' => 'Mot de passe réinitialisé avec succès'], 200);
    }
}

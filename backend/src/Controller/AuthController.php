<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Auth\RegisterRequestDTO;
use App\DTO\Auth\UserResponseDTO;
use App\Entity\User;
use App\Service\DtoValidatorService;
use App\Service\EmailVerificationService;
use App\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Contrôleur d'authentification.
 */
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly DtoValidatorService $dtoValidator,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * Enregistre un nouvel utilisateur.
     */
    #[OA\Post(
        path: '/api/register',
        summary: 'Inscription d\'un nouvel utilisateur',
        tags: ['Authentification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'pseudo', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'pseudo', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Utilisateur créé avec succès'),
            new OA\Response(response: 409, description: 'Email ou pseudo déjà existant'),
            new OA\Response(response: 422, description: 'Données invalides'),
        ],
    )]
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        EmailVerificationService $emailVerificationService,
    ): JsonResponse {
        ['dto' => $dto, 'errors' => $errors] = $this->dtoValidator->validateDto(
            $request->getContent(),
            RegisterRequestDTO::class,
        );

        if ($errors) {
            return $errors;
        }

        \assert($dto instanceof RegisterRequestDTO, 'DTO should not be null after validation');

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $dto->email]);
        if ($existingUser) {
            return $this->json(['error' => 'Email already exists'], 409);
        }

        $existingPseudo = $em->getRepository(User::class)->findOneBy(['pseudo' => $dto->pseudo]);
        if ($existingPseudo) {
            return $this->json(['error' => 'Pseudo already exists'], 409);
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setPseudo($dto->pseudo);
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);

        $hashedPassword = $passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        $emailVerificationService->sendVerificationEmail($user);

        $userResponse = UserResponseDTO::fromEntity($user);

        return new JsonResponse([
            'message' => 'User created successfully',
            'user' => json_decode($this->serializer->serialize($userResponse, 'json', ['groups' => 'user:read'])),
        ], 201);
    }

    /**
     * Récupère les informations de l'utilisateur connecté.
     */
    #[OA\Get(
        path: '/api/me',
        summary: 'Récupère les informations de l\'utilisateur connecté',
        security: [['BearerAuth' => []]],
        tags: ['Authentification'],
        responses: [
            new OA\Response(response: 200, description: 'Informations utilisateur'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ],
    )]
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'pseudo' => $user->getPseudo(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'timezone' => $user->getTimezone(),
            'language' => $user->getLanguage(),
            'avatar' => $user->getAvatar(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'updatedAt' => $user->getUpdatedAt()->format('c'),
        ]);
    }

    /**
     * Déconnecte l'utilisateur en supprimant le cookie JWT.
     */
    #[OA\Post(
        path: '/api/logout',
        summary: 'Déconnexion de l\'utilisateur',
        tags: ['Authentification'],
        responses: [
            new OA\Response(response: 200, description: 'Déconnexion réussie'),
        ],
    )]
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse(['message' => 'Déconnexion réussie']);

        $isProduction = ($_ENV['APP_ENV'] ?? 'dev') === 'prod';

        $response->headers->clearCookie(
            'jwt_token',
            '/',
            null,
            $isProduction,
            true,
            Cookie::SAMESITE_STRICT,
        );

        return $response;
    }

    /**
     * Vérifie un token de validation d'email.
     */
    #[Route('/api/auth/verify-email', name: 'api_auth_verify_email', methods: ['POST'])]
    public function verifyEmail(
        Request $request,
        EmailVerificationService $emailVerificationService,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? '';

        if (!$token) {
            return $this->json(['error' => 'Token manquant.'], 400);
        }

        $user = $emailVerificationService->verifyToken($token);

        if (!$user) {
            return $this->json(['error' => 'Ce lien de vérification est invalide ou a expiré.'], 400);
        }

        return $this->json(['message' => 'Votre email a été vérifié avec succès.']);
    }

    /**
     * Renvoie un email de vérification.
     * Accepte un { email } si non authentifié (ex: page post-inscription).
     */
    #[Route('/api/auth/resend-verification', name: 'api_auth_resend_verification', methods: ['POST'])]
    public function resendVerification(
        Request $request,
        #[CurrentUser] ?User $user,
        EmailVerificationService $emailVerificationService,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$user) {
            $data = json_decode($request->getContent(), true);
            $email = trim($data['email'] ?? '');

            if (!$email) {
                return $this->json(['error' => 'Non authentifié.'], 401);
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                return $this->json(['message' => 'Si un compte correspond, un email sera envoyé.']);
            }
        }

        if ($user->isVerified()) {
            return $this->json(['error' => 'Votre email est déjà vérifié.'], 400);
        }

        if (!$emailVerificationService->canResend($user)) {
            return $this->json(['error' => 'Veuillez attendre 2 minutes avant de renvoyer un email.'], 429);
        }

        $emailVerificationService->sendVerificationEmail($user);

        return $this->json(['message' => 'Email de vérification renvoyé.']);
    }

    /**
     * Envoie un email de réinitialisation de mot de passe.
     */
    #[Route('/api/auth/forgot-password', name: 'api_auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        PasswordResetService $passwordResetService,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');

        if (!$email || !filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse email invalide.'], 400);
        }

        $passwordResetService->sendResetEmail($email);

        return $this->json([
            'message' => 'Si un compte correspond à cet email, vous recevrez un lien de réinitialisation sous peu.',
        ]);
    }

    /**
     * Réinitialise le mot de passe via un token.
     */
    #[Route('/api/auth/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        PasswordResetService $passwordResetService,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? '';
        $password = $data['password'] ?? '';

        if (!$token) {
            return $this->json(['error' => 'Token manquant.'], 400);
        }

        if (\strlen($password) < 8) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 8 caractères.'], 400);
        }

        $success = $passwordResetService->resetPassword($token, $password);

        if (!$success) {
            return $this->json(['error' => 'Ce lien de réinitialisation est invalide ou a expiré.'], 400);
        }

        return $this->json(['message' => 'Votre mot de passe a été réinitialisé avec succès.']);
    }
}

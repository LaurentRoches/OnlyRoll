<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Profile\PasswordChangeDTO;
use App\DTO\Profile\ProfileUpdateDTO;
use App\Exception\Profile\InvalidPasswordException;
use App\Service\FileUploader;
use App\Service\ProfileService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Contrôleur de gestion du profil utilisateur.
 */
#[Route('/api/profile', name: 'api_profile_')]
#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly ProfileService $profileService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly FileUploader $fileUploader,
    ) {
    }

    /**
     * Récupère le profil de l'utilisateur connecté.
     */
    #[Route('', name: 'show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /**
     * Met à jour le profil de l'utilisateur connecté.
     */
    #[Route('', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                ProfileUpdateDTO::class,
                'json',
            );

            $errors = $this->validator->validate($dto);
            if (\count($errors) > 0) {
                return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $updatedUser = $this->profileService->updateProfile($user, $dto);

            return $this->json($updatedUser, Response::HTTP_OK, [], ['groups' => 'user:read']);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Change le mot de passe de l'utilisateur.
     * Rate limiting: 3 tentatives par heure.
     */
    #[Route('/password', name: 'change_password', methods: ['PUT'])]
    public function changePassword(
        Request $request,
        #[Autowire(service: 'password_change_limiter.limiter')]
        RateLimiterFactory $passwordChangeLimiter,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $limiter = $passwordChangeLimiter->create($user->getUserIdentifier());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(
                ['error' => 'Trop de tentatives de changement de mot de passe. Veuillez réessayer dans une heure.'],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                PasswordChangeDTO::class,
                'json',
            );

            $errors = $this->validator->validate($dto);
            if (\count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return $this->json([
                    'error' => 'Validation failed',
                    'violations' => $errorMessages,
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->profileService->changePassword($user, $dto);

            return $this->json(['message' => 'Mot de passe modifié avec succès']);
        } catch (InvalidPasswordException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->json(['error' => 'Une erreur est survenue'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload un avatar pour l'utilisateur.
     * Validation: MIME types images, taille max 2MB.
     */
    #[Route('/avatar', name: 'upload_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        /** @var UploadedFile|null $file */
        $file = $request->files->get('avatar');

        if (!$file) {
            return $this->json(['error' => 'Aucun fichier fourni'], Response::HTTP_BAD_REQUEST);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!\in_array($file->getMimeType(), $allowedMimeTypes, true)) {
            return $this->json(
                ['error' => 'Type de fichier non autorisé. Formats acceptés : JPEG, PNG, GIF, WebP'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $maxSize = 2 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            return $this->json(
                ['error' => 'Le fichier est trop volumineux. Taille maximum : 2 Mo'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $avatarPath = $this->fileUploader->uploadAvatar($file, $user);

            $this->profileService->updateAvatar($user, $avatarPath);

            return $this->json([
                'message' => 'Avatar mis à jour avec succès',
                'avatar' => $avatarPath,
            ]);
        } catch (Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'upload de l\'avatar'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprime l'avatar de l'utilisateur.
     */
    #[Route('/avatar', name: 'delete_avatar', methods: ['DELETE'])]
    public function deleteAvatar(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            if ($user->getAvatar()) {
                $this->fileUploader->deleteAvatar($user->getAvatar());
            }

            $this->profileService->removeAvatar($user);

            return $this->json(['message' => 'Avatar supprimé avec succès']);
        } catch (Exception $e) {
            return $this->json(['error' => 'Erreur lors de la suppression de l\'avatar'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

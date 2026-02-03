<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Security\EvaluatePasswordDTO;
use App\DTO\Security\GeneratePasswordDTO;
use App\Service\DtoValidatorService;
use App\Service\PasswordGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour les fonctionnalités de sécurité publiques.
 *
 * Expose les services de génération et d'évaluation de mots de passe.
 */
#[Route('/api/security', name: 'api_security_')]
final class SecurityController extends AbstractController
{
    public function __construct(
        private readonly PasswordGeneratorService $passwordGenerator,
        private readonly DtoValidatorService $dtoValidator,
    ) {
    }

    /**
     * Génère un ou plusieurs mots de passe sécurisés.
     *
     * Endpoint public pour permettre la génération sur les pages
     * d'inscription et de changement de mot de passe.
     */
    #[Route('/generate-password', name: 'generate_password', methods: ['POST'])]
    public function generatePassword(
        Request $request,
        #[Autowire(service: 'limiter.password_change_limiter')]
        RateLimiterFactory $passwordChangeLimiter,
    ): JsonResponse {
        $limiter = $passwordChangeLimiter->create($request->getClientIp() ?? 'unknown');

        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json([
                'error' => 'Trop de requêtes. Veuillez réessayer plus tard.',
            ], 429);
        }

        $content = $request->getContent();
        if ('' === $content || '{}' === $content) {
            $content = '{}';
        }

        ['dto' => $dto, 'errors' => $errors] = $this->dtoValidator->validateDto(
            $content,
            GeneratePasswordDTO::class,
        );

        if ($errors) {
            return $errors;
        }

        \assert($dto instanceof GeneratePasswordDTO);

        try {
            if ($dto->count > 1) {
                $passwords = $this->passwordGenerator->generateMultiple(
                    $dto->count,
                    $dto->length,
                    $dto->includeLowercase,
                    $dto->includeUppercase,
                    $dto->includeDigits,
                    $dto->includeSpecial,
                    $dto->excludeAmbiguous,
                );

                return $this->json([
                    'passwords' => $passwords,
                    'options' => [
                        'length' => $dto->length,
                        'includeLowercase' => $dto->includeLowercase,
                        'includeUppercase' => $dto->includeUppercase,
                        'includeDigits' => $dto->includeDigits,
                        'includeSpecial' => $dto->includeSpecial,
                        'excludeAmbiguous' => $dto->excludeAmbiguous,
                    ],
                ]);
            }

            $password = $this->passwordGenerator->generate(
                $dto->length,
                $dto->includeLowercase,
                $dto->includeUppercase,
                $dto->includeDigits,
                $dto->includeSpecial,
                $dto->excludeAmbiguous,
            );

            return $this->json([
                'password' => $password,
                'options' => [
                    'length' => $dto->length,
                    'includeLowercase' => $dto->includeLowercase,
                    'includeUppercase' => $dto->includeUppercase,
                    'includeDigits' => $dto->includeDigits,
                    'includeSpecial' => $dto->includeSpecial,
                    'excludeAmbiguous' => $dto->excludeAmbiguous,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Évalue la force d'un mot de passe.
     *
     * Retourne un score, une description de la force et des suggestions
     * d'amélioration si nécessaire.
     */
    #[Route('/evaluate-password', name: 'evaluate_password', methods: ['POST'])]
    public function evaluatePassword(Request $request): JsonResponse
    {
        ['dto' => $dto, 'errors' => $errors] = $this->dtoValidator->validateDto(
            $request->getContent(),
            EvaluatePasswordDTO::class,
        );

        if ($errors) {
            return $errors;
        }

        \assert($dto instanceof EvaluatePasswordDTO);

        $evaluation = $this->passwordGenerator->evaluateStrength($dto->password);

        return $this->json($evaluation);
    }

    /**
     * Retourne les options par défaut de génération de mot de passe.
     *
     * Utile pour le frontend pour initialiser le formulaire de génération.
     */
    #[Route('/password-options', name: 'password_options', methods: ['GET'])]
    public function getPasswordOptions(): JsonResponse
    {
        return $this->json([
            'defaults' => [
                'length' => 16,
                'minLength' => 12,
                'maxLength' => 128,
                'includeLowercase' => true,
                'includeUppercase' => true,
                'includeDigits' => true,
                'includeSpecial' => true,
                'excludeAmbiguous' => false,
            ],
            'specialCharacters' => '!@#$%&*()_+-=[]{}|;:,.<>?',
            'ambiguousCharacters' => ['0', 'O', 'o', '1', 'l', 'I'],
        ]);
    }
}

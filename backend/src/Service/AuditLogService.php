<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Enum\AuditAction;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service de gestion des logs d'audit pour la sécurité (OWASP A09).
 * Les IPs sont hashées pour la conformité RGPD.
 * Les mots de passe ne sont jamais loggés.
 */
class AuditLogService
{
    private const SENSITIVE_FIELDS = [
        'password',
        'plainPassword',
        'newPassword',
        'currentPassword',
        'oldPassword',
        'token',
        'secret',
        'apiKey',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly string $ipHashSecret = '',
    ) {
    }

    /**
     * Enregistre une action dans le log d'audit.
     *
     * @param AuditAction $action Type d'action
     * @param User|null $performer Utilisateur qui effectue l'action
     * @param User|null $targetUser Utilisateur cible de l'action
     * @param string|null $entityType Type d'entité concernée
     * @param int|null $entityId ID de l'entité concernée
     * @param array<string, mixed> $details Détails supplémentaires (sans données sensibles)
     * @param Request|null $request Requête HTTP (optionnel)
     */
    public function log(
        AuditAction $action,
        ?User $performer = null,
        ?User $targetUser = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $details = [],
        ?Request $request = null,
    ): AuditLog {
        $request = $request ?? $this->requestStack->getCurrentRequest();

        $sanitizedDetails = $this->sanitizeDetails($details);

        $auditLog = new AuditLog();
        $auditLog->setAction($action)
            ->setPerformer($performer)
            ->setTargetUser($targetUser)
            ->setEntityType($entityType)
            ->setEntityId($entityId)
            ->setDetails($sanitizedDetails);

        if ($request) {
            $auditLog->setIpAddress($this->hashIpAddress($request->getClientIp()))
                ->setUserAgent($this->truncateUserAgent($request->headers->get('User-Agent')));
        }

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();

        $this->logger->info('Audit log created', [
            'action' => $action->value,
            'performer_id' => $performer?->getId(),
            'target_user_id' => $targetUser?->getId(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'severity' => $action->getSeverity(),
        ]);

        return $auditLog;
    }

    /**
     * Hash l'adresse IP pour conformité RGPD.
     * Utilise SHA-256 avec un sel secret pour l'anonymisation.
     */
    public function hashIpAddress(?string $ip): ?string
    {
        if (null === $ip || '' === $ip) {
            return null;
        }

        $salt = $this->ipHashSecret ?: $_ENV['APP_SECRET'] ?? 'default-salt';

        return hash('sha256', $ip . $salt);
    }

    /**
     * Tronque le User-Agent pour éviter les débordements.
     */
    private function truncateUserAgent(?string $userAgent): ?string
    {
        if (null === $userAgent) {
            return null;
        }

        return mb_substr($userAgent, 0, 500);
    }

    /**
     * Supprime les données sensibles des détails.
     *
     * @param array<string, mixed> $details
     *
     * @return array<string, mixed>
     */
    private function sanitizeDetails(array $details): array
    {
        $sanitized = [];

        foreach ($details as $key => $value) {
            if (\in_array(strtolower($key), array_map('strtolower', self::SENSITIVE_FIELDS), true)) {
                continue;
            }

            if (\is_array($value)) {
                $sanitized[$key] = $this->sanitizeDetails($value);
            }
            else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Log un échec d'authentification.
     */
    public function logLoginFailure(string $email, ?Request $request = null): AuditLog
    {
        return $this->log(
            AuditAction::LOGIN_FAILED,
            null,
            null,
            'user',
            null,
            ['email' => $email, 'reason' => 'invalid_credentials'],
            $request,
        );
    }

    /**
     * Log une connexion réussie.
     */
    public function logLoginSuccess(User $user, ?Request $request = null): AuditLog
    {
        return $this->log(
            AuditAction::LOGIN_SUCCESS,
            $user,
            $user,
            'user',
            $user->getId(),
            [],
            $request,
        );
    }

    /**
     * Log un changement de mot de passe réussi.
     */
    public function logPasswordChange(User $user, ?Request $request = null): AuditLog
    {
        return $this->log(
            AuditAction::PASSWORD_CHANGE,
            $user,
            $user,
            'user',
            $user->getId(),
            [],
            $request,
        );
    }

    /**
     * Log un échec de changement de mot de passe.
     */
    public function logPasswordChangeFailed(User $user, string $reason, ?Request $request = null): AuditLog
    {
        return $this->log(
            AuditAction::PASSWORD_CHANGE_FAILED,
            $user,
            $user,
            'user',
            $user->getId(),
            ['reason' => $reason],
            $request,
        );
    }

    /**
     * Log le verrouillage d'un compte.
     */
    public function logAccountLocked(User $user, ?User $performer = null, ?Request $request = null): AuditLog
    {
        return $this->log(
            AuditAction::ACCOUNT_LOCKED,
            $performer ?? $user,
            $user,
            'user',
            $user->getId(),
            ['locked_until' => $user->getLockedUntil()?->format('c')],
            $request,
        );
    }

    /**
     * Log le déverrouillage d'un compte.
     */
    public function logAccountUnlocked(User $user, User $performer, ?Request $request = null): AuditLog
    {
        return $this->log(
            AuditAction::ACCOUNT_UNLOCKED,
            $performer,
            $user,
            'user',
            $user->getId(),
            [],
            $request,
        );
    }

    /**
     * Log l'accès à l'administration.
     */
    public function logAdminAccess(User $admin, string $section, ?Request $request = null): AuditLog
    {
        return $this->log(
            AuditAction::ADMIN_ACCESS,
            $admin,
            null,
            'admin',
            null,
            ['section' => $section],
            $request,
        );
    }

    /**
     * Log une action d'administration.
     *
     * @param array<string, mixed> $details
     */
    public function logAdminAction(
        User $admin,
        ?User $targetUser,
        string $action,
        array $details = [],
        ?Request $request = null,
    ): AuditLog {
        return $this->log(
            AuditAction::ADMIN_ACTION,
            $admin,
            $targetUser,
            'admin',
            $targetUser?->getId(),
            array_merge(['admin_action' => $action], $details),
            $request,
        );
    }
}

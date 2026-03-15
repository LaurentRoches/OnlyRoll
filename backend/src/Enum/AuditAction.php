<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Actions auditables pour le système de logs de sécurité.
 */
enum AuditAction: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILED = 'login_failed';
    case LOGOUT = 'logout';
    case ACCOUNT_LOCKED = 'account_locked';
    case ACCOUNT_UNLOCKED = 'account_unlocked';

    case PASSWORD_CHANGE = 'password_change';
    case PASSWORD_CHANGE_FAILED = 'password_change_failed';
    case PASSWORD_RESET_REQUEST = 'password_reset_request';
    case PASSWORD_RESET_COMPLETE = 'password_reset_complete';

    case USER_CREATED = 'user_created';
    case USER_UPDATED = 'user_updated';
    case USER_ACTIVATED = 'user_activated';
    case USER_DEACTIVATED = 'user_deactivated';
    case USER_SOFT_DELETED = 'user_soft_deleted';
    case USER_RESTORED = 'user_restored';
    case USER_ROLE_CHANGED = 'user_role_changed';

    case PROFILE_UPDATED = 'profile_updated';
    case AVATAR_CHANGED = 'avatar_changed';
    case EMAIL_CHANGED = 'email_changed';

    case ADMIN_ACCESS = 'admin_access';
    case ADMIN_ACTION = 'admin_action';

    case WIKI_FAVORITE_ADD = 'wiki_favorite_add';
    case WIKI_FAVORITE_REMOVE = 'wiki_favorite_remove';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOGIN_SUCCESS => 'Connexion réussie',
            self::LOGIN_FAILED => 'Échec de connexion',
            self::LOGOUT => 'Déconnexion',
            self::ACCOUNT_LOCKED => 'Compte verrouillé',
            self::ACCOUNT_UNLOCKED => 'Compte déverrouillé',
            self::PASSWORD_CHANGE => 'Mot de passe modifié',
            self::PASSWORD_CHANGE_FAILED => 'Échec modification mot de passe',
            self::PASSWORD_RESET_REQUEST => 'Demande réinitialisation mot de passe',
            self::PASSWORD_RESET_COMPLETE => 'Réinitialisation mot de passe terminée',
            self::USER_CREATED => 'Utilisateur créé',
            self::USER_UPDATED => 'Utilisateur modifié',
            self::USER_ACTIVATED => 'Utilisateur activé',
            self::USER_DEACTIVATED => 'Utilisateur désactivé',
            self::USER_SOFT_DELETED => 'Utilisateur supprimé (soft)',
            self::USER_RESTORED => 'Utilisateur restauré',
            self::USER_ROLE_CHANGED => 'Rôle utilisateur modifié',
            self::PROFILE_UPDATED => 'Profil mis à jour',
            self::AVATAR_CHANGED => 'Avatar modifié',
            self::EMAIL_CHANGED => 'Email modifié',
            self::ADMIN_ACCESS => 'Accès administration',
            self::ADMIN_ACTION => 'Action administration',
            self::WIKI_FAVORITE_ADD => 'Favori wiki ajouté',
            self::WIKI_FAVORITE_REMOVE => 'Favori wiki supprimé',
        };
    }

    public function getSeverity(): string
    {
        return match ($this) {
            self::LOGIN_FAILED,
            self::PASSWORD_CHANGE_FAILED,
            self::ACCOUNT_LOCKED => 'warning',
            self::USER_SOFT_DELETED,
            self::USER_DEACTIVATED,
            self::USER_ROLE_CHANGED => 'high',
            default => 'info',
        };
    }
}

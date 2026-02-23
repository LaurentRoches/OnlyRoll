<?php

declare(strict_types=1);

namespace App\Tests\Fuzzing;

/**
 * Fournit les payloads de fuzzing réutilisables dans tous les tests.
 * Chaque catégorie cible un type d'anomalie spécifique.
 */
class FuzzPayloadProvider
{
    /**
     * Valeurs nulles et types incorrects.
     * Cible : validation de type et gestion du null.
     */
    public static function typePayloads(): array
    {
        return [
            null,
            '',
            0,
            false,
            true,
            [],
            [['nested' => 'array']],
            3.14,
            -1,
            PHP_INT_MAX,
            PHP_INT_MIN,
        ];
    }

    /**
     * Injections SQL classiques.
     * Cible : failles SQLi si Doctrine/PDO mal utilisé.
     */
    public static function sqlInjectionPayloads(): array
    {
        return [
            "' OR '1'='1",
            "'; DROP TABLE users;--",
            "' UNION SELECT * FROM users--",
            "1; SELECT * FROM users",
            "admin'--",
            "' OR 1=1#",
            "\" OR \"1\"=\"1",
        ];
    }

    /**
     * Injections XSS.
     * Cible : sanitisation des sorties API et stockage en BDD.
     */
    public static function xssPayloads(): array
    {
        return [
            '<script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
            '"><svg onload=alert(1)>',
            "javascript:alert('xss')",
            '<iframe src="javascript:alert(1)">',
            '&lt;script&gt;alert(1)&lt;/script&gt;',
        ];
    }

    /**
     * Dépassements de taille.
     * Cible : limites de longueur des champs, mémoire, timeout.
     */
    public static function overflowPayloads(): array
    {
        return [
            str_repeat('A', 1000),
            str_repeat('A', 10000),
            str_repeat('A', 100000),
            str_repeat('🎲', 500),
            str_repeat("\x00", 1000),
        ];
    }

    /**
     * Caractères spéciaux et encodages.
     * Cible : gestion UTF-8, caractères de contrôle, encodages.
     */
    public static function encodingPayloads(): array
    {
        return [
            "💀🎲\x00\n\t\r",
            "\u{0000}",
            "\u{FEFF}",
            "Ωåß∂ƒ©˙∆˚¬",
            "%00%0d%0a",
            "\xff\xfe",
            "İ",
        ];
    }

    /**
     * Path traversal.
     * Cible : accès non autorisé au système de fichiers.
     */
    public static function pathTraversalPayloads(): array
    {
        return [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32',
            '%2e%2e%2f%2e%2e%2f',
            '....//....//etc/passwd',
        ];
    }

    /**
     * Ensemble complet pour les champs texte généraux.
     */
    public static function allTextPayloads(): array
    {
        return array_merge(
            self::typePayloads(),
            self::sqlInjectionPayloads(),
            self::xssPayloads(),
            self::overflowPayloads(),
            self::encodingPayloads(),
        );
    }

    /**
     * Codes HTTP considérés comme comportements normaux (non-fuzz).
     */
    public static function acceptableHttpCodes(): array
    {
        return [200, 201, 400, 401, 403, 404, 422];
    }
}

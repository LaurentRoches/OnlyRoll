<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use Wikimedia\CommonPasswords\CommonPasswords;

/**
 * Service de génération de mots de passe sécurisés.
 *
 * Respecte les recommandations NIST SP 800-63B et OWASP :
 * - Longueur minimale de 12 caractères
 * - Présence obligatoire de majuscules, minuscules, chiffres et caractères spéciaux
 * - Exclusion des mots de passe trop courants
 */
final class PasswordGeneratorService
{
    private const MIN_LENGTH = 12;
    private const MAX_LENGTH = 128;
    private const MAX_GENERATION_ATTEMPTS = 100;

    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const DIGITS = '0123456789';
    private const SPECIAL = '!@#$%&*()_+-=[]{}|;:,.<>?';

    /**
     * Génère un mot de passe sécurisé.
     *
     * @param int  $length              Longueur souhaitée (min: 12, max: 128)
     * @param bool $includeLowercase    Inclure des minuscules (défaut: true)
     * @param bool $includeUppercase    Inclure des majuscules (défaut: true)
     * @param bool $includeDigits       Inclure des chiffres (défaut: true)
     * @param bool $includeSpecial      Inclure des caractères spéciaux (défaut: true)
     * @param bool $excludeAmbiguous    Exclure les caractères ambigus (0/O, 1/l/I)
     * @param bool $checkCommonPassword Vérifier contre les mots de passe courants (défaut: true)
     *
     * @throws InvalidArgumentException Si les paramètres sont invalides
     *
     * @return string Le mot de passe généré
     */
    public function generate(
        int $length = 16,
        bool $includeLowercase = true,
        bool $includeUppercase = true,
        bool $includeDigits = true,
        bool $includeSpecial = true,
        bool $excludeAmbiguous = false,
        bool $checkCommonPassword = true,
    ): string {
        $this->validateLength($length);
        $this->validateCharacterSets($includeLowercase, $includeUppercase, $includeDigits, $includeSpecial);

        $charSets = $this->buildCharacterSets(
            $includeLowercase,
            $includeUppercase,
            $includeDigits,
            $includeSpecial,
            $excludeAmbiguous
        );

        $attempts = 0;
        do {
            $password = $this->generatePassword($length, $charSets);
            ++$attempts;

            if ($attempts >= self::MAX_GENERATION_ATTEMPTS) {
                break;
            }
        } while ($checkCommonPassword && CommonPasswords::isCommon($password));

        return $password;
    }

    /**
     * Génère plusieurs mots de passe sécurisés.
     *
     * @return string[]
     */
    public function generateMultiple(
        int $count = 5,
        int $length = 16,
        bool $includeLowercase = true,
        bool $includeUppercase = true,
        bool $includeDigits = true,
        bool $includeSpecial = true,
        bool $excludeAmbiguous = false,
    ): array {
        $passwords = [];
        for ($i = 0; $i < $count; ++$i) {
            $passwords[] = $this->generate(
                $length,
                $includeLowercase,
                $includeUppercase,
                $includeDigits,
                $includeSpecial,
                $excludeAmbiguous
            );
        }

        return $passwords;
    }

    /**
     * Évalue la force d'un mot de passe.
     *
     * @return array{score: int, strength: string, feedback: string[], isValid: bool}
     */
    public function evaluateStrength(string $password): array
    {
        $score = 0;
        $feedback = [];

        $length = mb_strlen($password);
        if ($length >= 12) {
            ++$score;
        } else {
            $feedback[] = 'Au moins 12 caractères requis';
        }

        if ($length >= 16) {
            ++$score;
        }

        if (preg_match('/[a-z]/', $password)) {
            ++$score;
        } else {
            $feedback[] = 'Au moins une minuscule requise';
        }

        if (preg_match('/[A-Z]/', $password)) {
            ++$score;
        } else {
            $feedback[] = 'Au moins une majuscule requise';
        }

        if (preg_match('/\d/', $password)) {
            ++$score;
        } else {
            $feedback[] = 'Au moins un chiffre requis';
        }

        if (preg_match('/[!@#$%&*()_+\-=\[\]{}|;:,.<>?]/', $password)) {
            ++$score;
        } else {
            $feedback[] = 'Au moins un caractère spécial requis (!@#$%&*()_+-=[]{}|;:,.<>?)';
        }

        if (CommonPasswords::isCommon($password)) {
            $score = max(0, $score - 3);
            $feedback[] = 'Ce mot de passe est trop courant';
        }

        $strength = match (true) {
            $score >= 6 => 'excellent',
            $score >= 5 => 'fort',
            $score >= 4 => 'moyen',
            $score >= 2 => 'faible',
            default => 'très faible',
        };

        return [
            'score' => min($score, 6),
            'strength' => $strength,
            'feedback' => $feedback,
            'isValid' => $score >= 5 && [] === $feedback,
        ];
    }

    /**
     * Vérifie si un mot de passe respecte les critères de sécurité.
     */
    public function isSecure(string $password): bool
    {
        $evaluation = $this->evaluateStrength($password);

        return $evaluation['isValid'];
    }

    private function validateLength(int $length): void
    {
        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('La longueur minimale est de %d caractères', self::MIN_LENGTH)
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('La longueur maximale est de %d caractères', self::MAX_LENGTH)
            );
        }
    }

    private function validateCharacterSets(
        bool $includeLowercase,
        bool $includeUppercase,
        bool $includeDigits,
        bool $includeSpecial,
    ): void {
        if (!$includeLowercase && !$includeUppercase && !$includeDigits && !$includeSpecial) {
            throw new InvalidArgumentException(
                'Au moins un type de caractère doit être sélectionné'
            );
        }
    }

    /**
     * @return array{chars: string, required: string[]}
     */
    private function buildCharacterSets(
        bool $includeLowercase,
        bool $includeUppercase,
        bool $includeDigits,
        bool $includeSpecial,
        bool $excludeAmbiguous,
    ): array {
        $allChars = '';
        $required = [];

        $lowercase = self::LOWERCASE;
        $uppercase = self::UPPERCASE;
        $digits = self::DIGITS;

        if ($excludeAmbiguous) {
            $lowercase = str_replace(['o', 'l'], '', $lowercase);
            $uppercase = str_replace(['O', 'I'], '', $uppercase);
            $digits = str_replace(['0', '1'], '', $digits);
        }

        if ($includeLowercase) {
            $allChars .= $lowercase;
            $required[] = $lowercase;
        }

        if ($includeUppercase) {
            $allChars .= $uppercase;
            $required[] = $uppercase;
        }

        if ($includeDigits) {
            $allChars .= $digits;
            $required[] = $digits;
        }

        if ($includeSpecial) {
            $allChars .= self::SPECIAL;
            $required[] = self::SPECIAL;
        }

        return [
            'chars' => $allChars,
            'required' => $required,
        ];
    }

    /**
     * @param array{chars: string, required: string[]} $charSets
     */
    private function generatePassword(int $length, array $charSets): string
    {
        $allChars = $charSets['chars'];
        $required = $charSets['required'];
        $allCharsLength = strlen($allChars);

        $password = [];
        foreach ($required as $charSet) {
            $password[] = $charSet[random_int(0, strlen($charSet) - 1)];
        }

        $remainingLength = $length - count($password);
        for ($i = 0; $i < $remainingLength; ++$i) {
            $password[] = $allChars[random_int(0, $allCharsLength - 1)];
        }

        shuffle($password);

        return implode('', $password);
    }
}

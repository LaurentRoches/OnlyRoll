<?php

declare(strict_types=1);

namespace App\Exception\Profile;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Exception de base pour les erreurs liées au profil utilisateur.
 */
class ProfileException extends Exception
{
    public function __construct(
        string $message = 'Erreur de profil',
        int $code = Response::HTTP_BAD_REQUEST,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

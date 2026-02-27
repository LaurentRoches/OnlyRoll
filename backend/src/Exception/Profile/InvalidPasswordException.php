<?php

declare(strict_types=1);

namespace App\Exception\Profile;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Exception levée lorsqu'un mot de passe est incorrect lors du changement.
 * Code HTTP : 400 (Bad Request).
 */
final class InvalidPasswordException extends ProfileException
{
    public function __construct(
        string $message = 'Mot de passe incorrect',
        int $code = Response::HTTP_BAD_REQUEST,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

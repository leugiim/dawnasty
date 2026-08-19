<?php

declare(strict_types=1);

namespace App\_Shared\Domain;

/**
 * Excepción que declara su propio código HTTP sin acoplar Domain/ ni
 * Application/ a Symfony. Las excepciones de dominio que representen un
 * error "esperado" del negocio (PlayerNotFoundException,
 * InvalidUuidFormatException, ...) la implementan.
 *
 * El listener de kernel.exception (_Shared/Infrastructure/Symfony/
 * EventListener/ApiExceptionListener) usa statusCode() y getMessage() para
 * construir la respuesta de error (ver docs/architecture.md, sección 7).
 */
interface ApiException extends \Throwable
{
    public function statusCode(): int;
}

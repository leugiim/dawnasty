<?php

declare(strict_types=1);

namespace App\_Shared\Domain;

/**
 * ApiException para errores de validación con detalle por campo. El
 * listener de kernel.exception usa errors() para rellenar el campo
 * "errors" de la respuesta de error (ver docs/architecture.md, sección 7).
 */
interface ApiValidationException extends ApiException
{
    /**
     * @return array<string, string>
     */
    public function errors(): array;
}

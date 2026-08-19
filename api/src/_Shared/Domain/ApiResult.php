<?php

declare(strict_types=1);

namespace App\_Shared\Domain;

/**
 * Resultado de un caso de uso listo para ser expuesto vía HTTP. PHP puro,
 * sin dependencias de framework, así que tanto DTOs de Application/ (los
 * *View que devuelven las Query) como clases de Domain/ pueden
 * implementarla sin romper la dirección de dependencias.
 *
 * Un controller puede devolver directamente un objeto que la implemente;
 * el listener de kernel.view (_Shared/Infrastructure/Symfony/EventListener/
 * ApiResultListener) lo envuelve en {"data": ...} con 200 (ver
 * docs/architecture.md, sección 7).
 */
interface ApiResult
{
    public function toPrimitives(): array;
}

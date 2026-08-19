<?php

declare(strict_types=1);

namespace App\_Shared\Infrastructure\Symfony\EventListener;

use App\_Shared\Domain\ApiResult;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Aplica el sobre (envelope) estándar de éxito a lo que devuelve un
 * controller (ver docs/architecture.md, sección 7). Solo se dispara cuando
 * el controller no ha devuelto ya una Response.
 *
 * - Controller devuelve un ApiResult (o ApiResultList) -> 200 con
 *   {"data": $result->toPrimitives()}.
 * - Controller devuelve void/null -> 204 sin cuerpo.
 */
#[AsEventListener(event: KernelEvents::VIEW)]
final class ApiResultListener
{
    public function __invoke(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result instanceof ApiResult) {
            $event->setResponse(new JsonResponse([
                'data' => $result->toPrimitives(),
            ], Response::HTTP_OK));

            return;
        }

        if ($result === null) {
            $event->setResponse(new Response(null, Response::HTTP_NO_CONTENT));
        }
    }
}

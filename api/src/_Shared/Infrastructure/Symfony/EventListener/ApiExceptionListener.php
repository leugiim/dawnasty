<?php

declare(strict_types=1);

namespace App\_Shared\Infrastructure\Symfony\EventListener;

use App\_Shared\Domain\ApiException;
use App\_Shared\Domain\ApiValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * Convierte cualquier excepción no capturada en el JSON de error estándar
 * (ver docs/architecture.md, sección 7):
 *
 * - ApiValidationException -> su statusCode(), su getMessage() como
 *   "message", y su errors() como "errors".
 * - ApiException (sin ser de validación) -> su statusCode() y
 *   getMessage() como "message", "errors": null.
 * - Cualquier otra excepción -> 500, mensaje genérico (no se filtra el
 *   mensaje real al cliente), y se loguea la excepción original completa.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApiExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $this->unwrap($event->getThrowable());

        if ($throwable instanceof ApiValidationException) {
            $event->setResponse(new JsonResponse([
                'message' => $throwable->getMessage(),
                'errors' => $throwable->errors(),
            ], $throwable->statusCode()));

            return;
        }

        if ($throwable instanceof ApiException) {
            $event->setResponse(new JsonResponse([
                'message' => $throwable->getMessage(),
                'errors' => null,
            ], $throwable->statusCode()));

            return;
        }

        $this->logger->error('Unhandled exception: {message}', [
            'message' => $throwable->getMessage(),
            'exception' => $throwable,
        ]);

        $event->setResponse(new JsonResponse([
            'message' => 'Internal server error',
            'errors' => null,
        ], Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    /**
     * Los buses de Messenger (command.bus/query.bus/event.bus) envuelven
     * cualquier excepción lanzada por un handler en HandlerFailedException.
     * Se desenvuelve aquí para juzgar la excepción de dominio real contra
     * ApiException/ApiValidationException.
     */
    private function unwrap(\Throwable $throwable): \Throwable
    {
        while ($throwable instanceof HandlerFailedException && $throwable->getPrevious() !== null) {
            $throwable = $throwable->getPrevious();
        }

        return $throwable;
    }
}

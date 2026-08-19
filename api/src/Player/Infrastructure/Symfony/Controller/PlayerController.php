<?php

declare(strict_types=1);

namespace App\Player\Infrastructure\Symfony\Controller;

use App\_Shared\Domain\ApiResult;
use App\Player\Application\Command\CreatePlayer\CreatePlayerCommand;
use App\Player\Application\Query\GetPlayer\GetPlayerQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller delgado: solo parsea el request, despacha al bus correspondiente
 * y devuelve el ApiResult/void resultante. Sin lógica de negocio ni
 * construcción manual de Response de éxito/error (ver docs/architecture.md,
 * sección 7): el listener de kernel.view construye el 200/204 de éxito y el
 * de kernel.exception construye el error a partir de las excepciones de
 * dominio que implementan ApiException.
 */
final class PlayerController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    #[Route('/api/players', name: 'api_players_create', methods: ['POST'])]
    public function create(Request $request): void
    {
        $payload = json_decode($request->getContent(), true) ?? [];

        $id = is_string($payload['id'] ?? null) ? $payload['id'] : '';
        $name = is_string($payload['name'] ?? null) ? $payload['name'] : '';

        $this->commandBus->dispatch(new CreatePlayerCommand(
            id: $id, // GUID (UUID v7) generado en el frontend
            name: $name,
        ));
    }

    #[Route('/api/players/{id}', name: 'api_players_get', methods: ['GET'])]
    public function get(string $id): ApiResult
    {
        $envelope = $this->queryBus->dispatch(new GetPlayerQuery(id: $id));

        /** @var ApiResult */
        return $envelope->last(HandledStamp::class)->getResult();
    }
}

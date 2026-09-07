<?php

declare(strict_types=1);

namespace App\Village\Infrastructure\Symfony\Controller;

use App\Village\Application\Command\FoundVillage\FoundVillageCommand;
use App\Village\Application\Query\GetVillage\GetVillageQuery;
use App\_Shared\Domain\ApiResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller delgado: solo parsea el request, despacha al bus
 * correspondiente y devuelve el ApiResult/void resultante (docs/
 * architecture.md, sección 7).
 */
final class VillageController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    #[Route('/api/villages', name: 'api_villages_found', methods: ['POST'])]
    public function found(Request $request): void
    {
        $payload = json_decode($request->getContent(), true) ?? [];

        $id = is_string($payload['id'] ?? null) ? $payload['id'] : '';

        $this->commandBus->dispatch(new FoundVillageCommand(
            id: $id, // GUID (UUID v7) generado en el frontend
        ));
    }

    #[Route('/api/villages/{id}', name: 'api_villages_get', methods: ['GET'])]
    public function get(string $id): ApiResult
    {
        $envelope = $this->queryBus->dispatch(new GetVillageQuery(id: $id));

        /** @var ApiResult */
        return $envelope->last(HandledStamp::class)->getResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Command\CreatePlayer\CreatePlayerCommand;
use App\Application\Query\GetPlayer\GetPlayerQuery;
use App\Application\Query\GetPlayer\PlayerView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller delgado: solo parsea el request, despacha al bus correspondiente
 * y mapea el resultado a una Response. Sin lógica de negocio.
 */
final class PlayerController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/players', name: 'api_players_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true) ?? [];

        $id = is_string($payload['id'] ?? null) ? $payload['id'] : '';
        $name = is_string($payload['name'] ?? null) ? $payload['name'] : '';

        $violations = $this->validator->validate($id, [
            new Assert\NotBlank(),
            new Assert\Uuid(),
        ]);

        if (count($violations) > 0 || $name === '') {
            return new JsonResponse([
                'error' => [
                    'message' => 'Validation failed',
                    'code' => 'VALIDATION_ERROR',
                    'details' => [
                        'id' => count($violations) > 0 ? ['id must be a valid UUID v4'] : [],
                        'name' => $name === '' ? ['name is required'] : [],
                    ],
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(new CreatePlayerCommand(
            id: $id, // GUID generado en el frontend
            name: $name,
        ));

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    #[Route('/api/players/{id}', name: 'api_players_get', methods: ['GET'])]
    public function get(string $id): Response
    {
        try {
            $envelope = $this->queryBus->dispatch(new GetPlayerQuery(id: $id));
        } catch (HandlerFailedException) {
            // id con formato inválido -> se trata como "no encontrado".
            return $this->notFound();
        }

        /** @var PlayerView|null $view */
        $view = $envelope->last(HandledStamp::class)?->getResult();

        if ($view === null) {
            return $this->notFound();
        }

        return new JsonResponse($view);
    }

    private function notFound(): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'message' => 'Player not found',
                'code' => 'PLAYER_NOT_FOUND',
            ],
        ], Response::HTTP_NOT_FOUND);
    }
}

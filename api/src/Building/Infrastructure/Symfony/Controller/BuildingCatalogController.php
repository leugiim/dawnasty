<?php

declare(strict_types=1);

namespace App\Building\Infrastructure\Symfony\Controller;

use App\Building\Application\Query\GetBuildingCatalog\GetBuildingCatalogQuery;
use App\_Shared\Domain\ApiResult;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final class BuildingCatalogController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    #[Route('/api/buildings/catalog', name: 'api_buildings_catalog', methods: ['GET'])]
    public function catalog(): ApiResult
    {
        $envelope = $this->queryBus->dispatch(new GetBuildingCatalogQuery());

        /** @var ApiResult */
        return $envelope->last(HandledStamp::class)->getResult();
    }
}

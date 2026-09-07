<?php

declare(strict_types=1);

namespace App\Village\Application\Query\GetVillage;

use App\Village\Domain\Exception\VillageNotFoundException;
use App\Village\Domain\VillageId;
use App\Village\Domain\VillageRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetVillageQueryHandler
{
    public function __construct(
        private VillageRepositoryInterface $villages,
    ) {
    }

    public function __invoke(GetVillageQuery $query): VillageView
    {
        $id = VillageId::fromString($query->id);

        $village = $this->villages->byId($id);

        if ($village === null) {
            throw VillageNotFoundException::withId($id);
        }

        return VillageView::fromVillage($village);
    }
}

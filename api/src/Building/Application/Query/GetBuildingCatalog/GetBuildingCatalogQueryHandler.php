<?php

declare(strict_types=1);

namespace App\Building\Application\Query\GetBuildingCatalog;

use App\Building\Domain\BuildingCatalog;
use App\_Shared\Domain\ApiResultList;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetBuildingCatalogQueryHandler
{
    public function __construct(
        private BuildingCatalog $catalog,
    ) {
    }

    public function __invoke(GetBuildingCatalogQuery $query): ApiResultList
    {
        return new ApiResultList(array_map(
            BuildingDefinitionView::fromDefinition(...),
            $this->catalog->all(),
        ));
    }
}

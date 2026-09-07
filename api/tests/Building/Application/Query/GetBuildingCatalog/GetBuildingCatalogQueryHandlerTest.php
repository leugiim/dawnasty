<?php

declare(strict_types=1);

namespace App\Tests\Building\Application\Query\GetBuildingCatalog;

use App\Building\Application\Query\GetBuildingCatalog\GetBuildingCatalogQuery;
use App\Building\Application\Query\GetBuildingCatalog\GetBuildingCatalogQueryHandler;
use App\Building\Domain\BuildingCatalog;
use App\Building\Domain\BuildingType;
use PHPUnit\Framework\TestCase;

final class GetBuildingCatalogQueryHandlerTest extends TestCase
{
    public function test_it_returns_one_view_per_building_type(): void
    {
        $handler = new GetBuildingCatalogQueryHandler(new BuildingCatalog());

        $result = ($handler)(new GetBuildingCatalogQuery());

        $primitives = $result->toPrimitives();

        self::assertCount(count(BuildingType::cases()), $primitives);
        self::assertEqualsCanonicalizing(
            array_map(static fn (BuildingType $type): string => $type->value, BuildingType::cases()),
            array_column($primitives, 'type'),
        );
    }

    public function test_the_house_view_exposes_its_level_table(): void
    {
        $handler = new GetBuildingCatalogQueryHandler(new BuildingCatalog());

        $primitives = ($handler)(new GetBuildingCatalogQuery())->toPrimitives();

        $house = current(array_filter($primitives, static fn (array $view): bool => $view['type'] === 'house'));

        self::assertNotFalse($house);
        self::assertFalse($house['producesFood']);
        self::assertSame(5, $house['levels'][0]['housingCapacity']);
    }
}

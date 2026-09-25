<?php

declare(strict_types=1);

namespace App\Tests\Building\Domain;

use App\Building\Domain\BuildingCatalog;
use App\Building\Domain\BuildingType;
use App\Village\Domain\ResourceType;
use PHPUnit\Framework\TestCase;

final class BuildingCatalogTest extends TestCase
{
    public function test_it_has_a_definition_for_every_building_type(): void
    {
        $catalog = new BuildingCatalog();

        $types = array_map(
            static fn ($definition) => $definition->type,
            $catalog->all(),
        );

        self::assertEqualsCanonicalizing(BuildingType::cases(), $types);
    }

    public function test_definition_for_returns_the_matching_definition(): void
    {
        $catalog = new BuildingCatalog();

        $definition = $catalog->definitionFor(BuildingType::Farm);

        self::assertSame(BuildingType::Farm, $definition->type);
        self::assertTrue($definition->producesFood);
    }

    public function test_only_the_farm_is_a_food_producer(): void
    {
        $catalog = new BuildingCatalog();

        foreach ($catalog->all() as $definition) {
            self::assertSame($definition->type === BuildingType::Farm, $definition->producesFood);
        }
    }

    public function test_level_returns_the_matching_level(): void
    {
        $catalog = new BuildingCatalog();

        $level = $catalog->definitionFor(BuildingType::House)->level(2);

        self::assertNotNull($level);
        self::assertSame(2, $level->level);
        self::assertSame(10, $level->housingCapacity);
    }

    public function test_level_returns_null_for_a_level_not_in_the_table(): void
    {
        $catalog = new BuildingCatalog();

        self::assertNull($catalog->definitionFor(BuildingType::House)->level(999));
    }

    public function test_the_main_building_is_free_at_level_1(): void
    {
        $catalog = new BuildingCatalog();

        $level1 = $catalog->definitionFor(BuildingType::MainBuilding)->level(1);

        self::assertNotNull($level1);
        self::assertSame([], $level1->cost);
    }

    public function test_the_farm_produces_food(): void
    {
        $catalog = new BuildingCatalog();

        $level1 = $catalog->definitionFor(BuildingType::Farm)->level(1);

        self::assertNotNull($level1);
        self::assertSame(ResourceType::Food, $level1->producedResource);
        self::assertGreaterThan(0, $level1->producedResourcePerHour);
    }
}

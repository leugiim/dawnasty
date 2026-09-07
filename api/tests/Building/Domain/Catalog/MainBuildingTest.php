<?php

declare(strict_types=1);

namespace App\Tests\Building\Domain\Catalog;

use App\Building\Domain\BuildingType;
use App\Building\Domain\Catalog\MainBuilding;
use PHPUnit\Framework\TestCase;

final class MainBuildingTest extends TestCase
{
    public function test_it_defines_the_main_building_type_and_does_not_produce_food(): void
    {
        $definition = (new MainBuilding())->define();

        self::assertSame(BuildingType::MainBuilding, $definition->type);
        self::assertFalse($definition->producesFood);
    }

    public function test_level_1_is_free_and_already_produces_villagers(): void
    {
        $level1 = (new MainBuilding())->define()->level(1);

        self::assertNotNull($level1);
        self::assertSame([], $level1->cost);
        self::assertGreaterThan(0, $level1->villagerProductionPerHour);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Building\Domain\Catalog;

use App\Building\Domain\BuildingType;
use App\Building\Domain\Catalog\House;
use PHPUnit\Framework\TestCase;

final class HouseTest extends TestCase
{
    public function test_it_defines_the_house_type_and_does_not_produce_food(): void
    {
        $definition = (new House())->define();

        self::assertSame(BuildingType::House, $definition->type);
        self::assertFalse($definition->producesFood);
    }

    public function test_housing_capacity_grows_with_the_level(): void
    {
        $definition = (new House())->define();

        self::assertSame(5, $definition->level(1)?->housingCapacity);
        self::assertSame(10, $definition->level(2)?->housingCapacity);
    }
}

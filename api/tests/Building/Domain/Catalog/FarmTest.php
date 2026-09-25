<?php

declare(strict_types=1);

namespace App\Tests\Building\Domain\Catalog;

use App\Building\Domain\BuildingType;
use App\Building\Domain\Catalog\Farm;
use App\Village\Domain\ResourceType;
use PHPUnit\Framework\TestCase;

final class FarmTest extends TestCase
{
    public function test_it_defines_the_farm_type_and_produces_food(): void
    {
        $definition = (new Farm())->define();

        self::assertSame(BuildingType::Farm, $definition->type);
        self::assertTrue($definition->producesFood);
    }

    public function test_it_produces_more_food_at_higher_levels(): void
    {
        $definition = (new Farm())->define();

        self::assertSame(ResourceType::Food, $definition->level(1)?->producedResource);
        self::assertLessThan(
            $definition->level(2)?->producedResourcePerHour,
            $definition->level(1)?->producedResourcePerHour,
        );
    }
}

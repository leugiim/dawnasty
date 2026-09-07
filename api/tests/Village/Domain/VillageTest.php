<?php

declare(strict_types=1);

namespace App\Tests\Village\Domain;

use App\Village\Domain\BuildingType;
use App\Village\Domain\Event\VillageFounded;
use App\Village\Domain\ResourceType;
use App\Village\Domain\Stage;
use App\Village\Domain\Village;
use App\Village\Domain\VillageId;
use PHPUnit\Framework\TestCase;

final class VillageTest extends TestCase
{
    private const VILLAGE_ID = '019299aa-0000-7000-8000-000000000001';

    public function test_found_creates_a_village_with_only_the_main_building_at_level_1(): void
    {
        $village = Village::found(VillageId::fromString(self::VILLAGE_ID), new \DateTimeImmutable('2026-09-07T12:00:00+00:00'));

        self::assertSame(self::VILLAGE_ID, (string) $village->id());
        self::assertSame(1, $village->mainBuildingLevel());
        self::assertSame(
            [BuildingType::MainBuilding->value => 1],
            $village->buildings(),
        );
    }

    public function test_found_starts_with_every_resource_type_at_zero(): void
    {
        $village = Village::found(VillageId::fromString(self::VILLAGE_ID), new \DateTimeImmutable());

        $expected = array_fill_keys(
            array_map(static fn (ResourceType $type): string => $type->value, ResourceType::cases()),
            0,
        );

        self::assertSame($expected, $village->resources());
    }

    public function test_found_starts_with_no_population(): void
    {
        $village = Village::found(VillageId::fromString(self::VILLAGE_ID), new \DateTimeImmutable());

        self::assertSame(0, $village->populationTotal());
        self::assertSame(0, $village->populationUnemployed());
    }

    public function test_housing_capacity_is_zero_without_a_house_built(): void
    {
        // Placeholder mientras no exista un edificio de vivienda construible
        // (ver Village::housingCapacity()).
        $village = Village::found(VillageId::fromString(self::VILLAGE_ID), new \DateTimeImmutable());

        self::assertSame(0, $village->housingCapacity());
    }

    public function test_found_stage_is_aldea_at_main_building_level_1(): void
    {
        $village = Village::found(VillageId::fromString(self::VILLAGE_ID), new \DateTimeImmutable());

        self::assertSame(Stage::Aldea, $village->stage());
    }

    public function test_found_records_the_last_calculated_at_it_was_given(): void
    {
        $foundedAt = new \DateTimeImmutable('2026-09-07T12:00:00+00:00');

        $village = Village::found(VillageId::fromString(self::VILLAGE_ID), $foundedAt);

        self::assertEquals($foundedAt, $village->lastCalculatedAt());
    }

    public function test_found_records_a_village_founded_domain_event(): void
    {
        $village = Village::found(VillageId::fromString(self::VILLAGE_ID), new \DateTimeImmutable());

        $events = $village->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(VillageFounded::class, $events[0]);
        self::assertSame(self::VILLAGE_ID, (string) $events[0]->villageId);
    }

    public function test_pull_domain_events_empties_the_buffer(): void
    {
        $village = Village::found(VillageId::fromString(self::VILLAGE_ID), new \DateTimeImmutable());

        $village->pullDomainEvents();

        self::assertSame([], $village->pullDomainEvents());
    }
}

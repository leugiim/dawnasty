<?php

declare(strict_types=1);

namespace App\Tests\Village\Application\Query\GetVillage;

use App\Tests\Village\Application\InMemoryVillageRepository;
use App\Village\Application\Query\GetVillage\GetVillageQuery;
use App\Village\Application\Query\GetVillage\GetVillageQueryHandler;
use App\Village\Domain\Exception\VillageNotFoundException;
use App\Village\Domain\Village;
use App\Village\Domain\VillageId;
use App\_Shared\Domain\Exception\InvalidUuidFormatException;
use PHPUnit\Framework\TestCase;

final class GetVillageQueryHandlerTest extends TestCase
{
    private const VILLAGE_ID = '019299aa-0000-7000-8000-000000000001';

    public function test_it_returns_the_view_of_an_existing_village(): void
    {
        $villages = new InMemoryVillageRepository();
        $villages->save(Village::found(
            VillageId::fromString(self::VILLAGE_ID),
            new \DateTimeImmutable('2026-09-07T12:00:00+00:00'),
        ));

        $handler = new GetVillageQueryHandler($villages);

        $view = ($handler)(new GetVillageQuery(id: self::VILLAGE_ID));

        self::assertSame(self::VILLAGE_ID, $view->id);
        self::assertSame('aldea', $view->stage);
        self::assertSame('Choza del Fundador', $view->mainBuildingName);
        self::assertSame(1, $view->mainBuildingLevel);
        self::assertSame(0, $view->populationTotal);
        self::assertSame(10, $view->housingCapacity);
    }

    public function test_it_throws_when_the_village_does_not_exist(): void
    {
        $handler = new GetVillageQueryHandler(new InMemoryVillageRepository());

        $this->expectException(VillageNotFoundException::class);

        ($handler)(new GetVillageQuery(id: self::VILLAGE_ID));
    }

    public function test_it_rejects_an_id_that_is_not_a_valid_uuid_v7(): void
    {
        $handler = new GetVillageQueryHandler(new InMemoryVillageRepository());

        $this->expectException(InvalidUuidFormatException::class);

        ($handler)(new GetVillageQuery(id: 'not-a-uuid'));
    }
}

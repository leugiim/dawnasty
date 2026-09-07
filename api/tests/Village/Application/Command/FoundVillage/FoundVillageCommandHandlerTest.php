<?php

declare(strict_types=1);

namespace App\Tests\Village\Application\Command\FoundVillage;

use App\Tests\Village\Application\InMemoryVillageRepository;
use App\Village\Application\Command\FoundVillage\FoundVillageCommand;
use App\Village\Application\Command\FoundVillage\FoundVillageCommandHandler;
use App\Village\Domain\Event\VillageFounded;
use App\Village\Domain\VillageId;
use App\_Shared\Domain\Exception\InvalidUuidFormatException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class FoundVillageCommandHandlerTest extends TestCase
{
    private const VILLAGE_ID = '019299aa-0000-7000-8000-000000000001';

    public function test_it_saves_a_new_village_and_publishes_village_founded(): void
    {
        $villages = new InMemoryVillageRepository();
        $publishedEvents = [];

        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (object $event) use (&$publishedEvents): Envelope {
                $publishedEvents[] = $event;

                return new Envelope($event);
            });

        $handler = new FoundVillageCommandHandler($villages, $eventBus, new MockClock('2026-09-07T12:00:00+00:00'));

        ($handler)(new FoundVillageCommand(id: self::VILLAGE_ID));

        $village = $villages->byId(VillageId::fromString(self::VILLAGE_ID));
        self::assertNotNull($village);
        self::assertSame(1, $village->mainBuildingLevel());
        self::assertEquals(new \DateTimeImmutable('2026-09-07T12:00:00+00:00'), $village->lastCalculatedAt());

        self::assertCount(1, $publishedEvents);
        self::assertInstanceOf(VillageFounded::class, $publishedEvents[0]);
        self::assertSame(self::VILLAGE_ID, (string) $publishedEvents[0]->villageId);
    }

    public function test_it_rejects_an_id_that_is_not_a_valid_uuid_v7(): void
    {
        // El id inválido revienta antes de llegar a despachar ningún evento,
        // así que el eventBus no necesita ninguna expectativa: un stub basta.
        $handler = new FoundVillageCommandHandler(
            new InMemoryVillageRepository(),
            $this->createStub(MessageBusInterface::class),
            new MockClock(),
        );

        $this->expectException(InvalidUuidFormatException::class);

        ($handler)(new FoundVillageCommand(id: 'not-a-uuid'));
    }
}

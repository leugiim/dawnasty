<?php

declare(strict_types=1);

namespace App\Tests\Village\Application;

use App\Village\Domain\Village;
use App\Village\Domain\VillageId;
use App\Village\Domain\VillageRepositoryInterface;

/**
 * Test double en memoria de VillageRepositoryInterface, para probar los
 * Command/QueryHandler de Application/ sin depender de Doctrine ni de una
 * base de datos real.
 */
final class InMemoryVillageRepository implements VillageRepositoryInterface
{
    /** @var array<string, Village> */
    private array $villages = [];

    public function save(Village $village): void
    {
        $this->villages[(string) $village->id()] = $village;
    }

    public function byId(VillageId $id): ?Village
    {
        return $this->villages[(string) $id] ?? null;
    }

    public function count(): int
    {
        return count($this->villages);
    }
}

<?php

declare(strict_types=1);

namespace App\Village\Application\Query\GetVillage;

use App\Village\Domain\Village;
use App\_Shared\Domain\ApiResult;

/**
 * Read model de salida para Village. Nunca se expone la entidad de dominio
 * fuera de Application/.
 */
final readonly class VillageView implements ApiResult
{
    /**
     * @param array<string, int> $buildings tipo de edificio => nivel
     * @param array<string, int> $resources tipo de recurso => cantidad
     */
    public function __construct(
        public string $id,
        public string $stage,
        public string $mainBuildingName,
        public int $mainBuildingLevel,
        public array $buildings,
        public array $resources,
        public int $populationTotal,
        public int $populationUnemployed,
        public int $housingCapacity,
        public \DateTimeImmutable $lastCalculatedAt,
    ) {
    }

    public static function fromVillage(Village $village): self
    {
        $stage = $village->stage();

        return new self(
            id: (string) $village->id(),
            stage: $stage->value,
            mainBuildingName: $stage->mainBuildingName(),
            mainBuildingLevel: $village->mainBuildingLevel(),
            buildings: $village->buildings(),
            resources: $village->resources(),
            populationTotal: $village->populationTotal(),
            populationUnemployed: $village->populationUnemployed(),
            housingCapacity: $village->housingCapacity(),
            lastCalculatedAt: $village->lastCalculatedAt(),
        );
    }

    public function toPrimitives(): array
    {
        return [
            'id' => $this->id,
            'stage' => $this->stage,
            'mainBuildingName' => $this->mainBuildingName,
            'mainBuildingLevel' => $this->mainBuildingLevel,
            'buildings' => $this->buildings,
            'resources' => $this->resources,
            'population' => [
                'total' => $this->populationTotal,
                'unemployed' => $this->populationUnemployed,
                'housingCapacity' => $this->housingCapacity,
            ],
            'lastCalculatedAt' => $this->lastCalculatedAt->format(DATE_ATOM),
        ];
    }
}

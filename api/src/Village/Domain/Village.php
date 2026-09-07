<?php

declare(strict_types=1);

namespace App\Village\Domain;

use App\Village\Domain\Event\VillageFounded;
use App\_Shared\Domain\Event\RecordsDomainEvents;

/**
 * Aggregate root del módulo Village (game-design.md, sección 3; docs/
 * tasks.md, módulo Village). Sin atributos de Doctrine: el mapping vive en
 * Infrastructure/Doctrine/Mapping/Village.orm.xml.
 *
 * Primera vuelta (docs/tasks.md, "Orden sugerido" paso 1): solo cubre
 * fundar y leer una Village con su Edificio Principal. Deliberadamente NO
 * incluye todavía progreso offline (paso 2), escasez de comida (paso 3) ni
 * llegada de aldeanos (paso 4) — $lastCalculatedAt y $populationTotal/
 * $populationUnemployed ya están modelados porque forman parte de la forma
 * del aggregate, pero de momento no los mueve ninguna lógica.
 */
final class Village
{
    use RecordsDomainEvents;

    /**
     * Capacidad de vivienda base, sin ninguna Casa construida (game-design.md,
     * 4.1). Valor placeholder pendiente de balance real, igual que el resto
     * de números del módulo Building — evita que una aldea recién fundada se
     * quede sin poder recibir ni un aldeano hasta construir la primera Casa.
     */
    private const BASE_HOUSING_CAPACITY = 10;

    /**
     * @param array<string, int> $buildings tipo de edificio (BuildingType::value) => nivel
     * @param array<string, int> $resources tipo de recurso (ResourceType::value) => cantidad
     */
    private function __construct(
        private readonly string $id,
        private array $buildings,
        private array $resources,
        private int $populationTotal,
        private int $populationUnemployed,
        private \DateTimeImmutable $lastCalculatedAt,
    ) {
    }

    public static function found(VillageId $id, \DateTimeImmutable $foundedAt): self
    {
        $village = new self(
            id: $id->value,
            buildings: [
                // Único edificio presente desde la fundación (game-design.md, 4.3).
                BuildingType::MainBuilding->value => 1,
            ],
            resources: array_fill_keys(
                array_map(static fn (ResourceType $type): string => $type->value, ResourceType::cases()),
                0,
            ),
            populationTotal: 0,
            populationUnemployed: 0,
            lastCalculatedAt: $foundedAt,
        );

        $village->record(new VillageFounded($id));

        return $village;
    }

    public function id(): VillageId
    {
        return VillageId::fromString($this->id);
    }

    /**
     * @return array<string, int>
     */
    public function buildings(): array
    {
        return $this->buildings;
    }

    public function mainBuildingLevel(): int
    {
        return $this->buildings[BuildingType::MainBuilding->value] ?? 0;
    }

    public function stage(): Stage
    {
        return Stage::fromMainBuildingLevel($this->mainBuildingLevel());
    }

    /**
     * @return array<string, int>
     */
    public function resources(): array
    {
        return $this->resources;
    }

    public function populationTotal(): int
    {
        return $this->populationTotal;
    }

    public function populationUnemployed(): int
    {
        return $this->populationUnemployed;
    }

    /**
     * Placeholder: de momento siempre BASE_HOUSING_CAPACITY, sin sumar nada
     * por Casas construidas (pendiente del módulo Building / UpgradeBuilding
     * — docs/tasks.md, "Orden sugerido" paso 5). game-design.md 4.1 dice que
     * las Casas dan capacidad de vivienda adicional; hasta que existan como
     * edificio construible, toda Village parte de esta base.
     */
    public function housingCapacity(): int
    {
        return self::BASE_HOUSING_CAPACITY;
    }

    public function lastCalculatedAt(): \DateTimeImmutable
    {
        return $this->lastCalculatedAt;
    }
}

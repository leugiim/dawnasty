<?php

declare(strict_types=1);

namespace App\Player\Domain;

use App\Player\Domain\Event\PlayerWasCreated;
use App\_Shared\Domain\Event\RecordsDomainEvents;

/**
 * Aggregate root del módulo Player.
 *
 * Sin atributos de Doctrine: el mapping vive en
 * Infrastructure/Doctrine/Mapping/Player.orm.xml. El id se guarda
 * internamente como string (tal cual lo persiste la columna "guid" de
 * Doctrine) y se expone al resto del dominio como PlayerId.
 */
final class Player
{
    use RecordsDomainEvents;

    private function __construct(
        private readonly string $id,
        private string $name,
    ) {
    }

    public static function create(PlayerId $id, string $name): self
    {
        $player = new self($id->value, $name);

        $player->record(new PlayerWasCreated($id));

        return $player;
    }

    public function id(): PlayerId
    {
        return PlayerId::fromString($this->id);
    }

    public function name(): string
    {
        return $this->name;
    }
}

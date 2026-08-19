<?php

declare(strict_types=1);

namespace App\_Shared\Domain\ValueObject;

use App\_Shared\Domain\Exception\InvalidUuidFormatException;

/**
 * VO base para todos los *Id del dominio.
 *
 * Valida que el valor recibido es un UUID v7 bien formado (forma general de
 * UUID y nibble de versión == 7). NUNCA genera un id nuevo por sí mismo --
 * no tiene método generate()/random(). Los ids siempre llegan ya asignados
 * desde el cliente (ver docs/architecture.md, sección 6).
 */
abstract class Id
{
    private const UUID_V7_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public readonly string $value;

    final public function __construct(string $value)
    {
        if (preg_match(self::UUID_V7_PATTERN, $value) !== 1) {
            throw InvalidUuidFormatException::forValue($value);
        }

        $this->value = $value;
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

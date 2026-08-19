<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class InvalidUuidFormatException extends \DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('"%s" is not a valid UUID v4.', $value));
    }
}

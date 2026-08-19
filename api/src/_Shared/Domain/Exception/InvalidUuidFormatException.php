<?php

declare(strict_types=1);

namespace App\_Shared\Domain\Exception;

final class InvalidUuidFormatException extends \DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('"%s" is not a valid UUID v7.', $value));
    }
}

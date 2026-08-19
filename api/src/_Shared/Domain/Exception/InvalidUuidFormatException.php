<?php

declare(strict_types=1);

namespace App\_Shared\Domain\Exception;

use App\_Shared\Domain\ApiException;

final class InvalidUuidFormatException extends \DomainException implements ApiException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('"%s" is not a valid UUID v7.', $value));
    }

    public function statusCode(): int
    {
        return 400;
    }
}

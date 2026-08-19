<?php

declare(strict_types=1);

namespace App\Player\Domain\Exception;

use App\_Shared\Domain\ApiException;
use App\Player\Domain\PlayerId;

final class PlayerNotFoundException extends \DomainException implements ApiException
{
    public static function withId(PlayerId $id): self
    {
        return new self(sprintf('Player "%s" not found.', $id));
    }

    public function statusCode(): int
    {
        return 404;
    }
}

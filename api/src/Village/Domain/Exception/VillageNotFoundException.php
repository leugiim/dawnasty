<?php

declare(strict_types=1);

namespace App\Village\Domain\Exception;

use App\Village\Domain\VillageId;
use App\_Shared\Domain\ApiException;

final class VillageNotFoundException extends \DomainException implements ApiException
{
    public static function withId(VillageId $id): self
    {
        return new self(sprintf('Village "%s" not found.', $id));
    }

    public function statusCode(): int
    {
        return 404;
    }
}

<?php

declare(strict_types=1);

namespace App\_Shared\Domain;

/**
 * ApiResult para una colección de ApiResult. Se construye con un iterable
 * de ApiResult (p. ej. iterable<PlayerView>) y su toPrimitives() es el
 * array_map de toPrimitives() sobre cada elemento.
 */
final readonly class ApiResultList implements ApiResult
{
    /**
     * @param iterable<ApiResult> $items
     */
    public function __construct(
        private iterable $items,
    ) {
    }

    public function toPrimitives(): array
    {
        return array_map(
            static fn (ApiResult $item): array => $item->toPrimitives(),
            is_array($this->items) ? $this->items : iterator_to_array($this->items),
        );
    }
}

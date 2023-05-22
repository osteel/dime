<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use EventSauce\EventSourcing\AggregateRootId as AggregateRootIdInterface;

abstract readonly class AggregateRootId implements AggregateRootIdInterface
{
    final private function __construct(protected readonly string $id)
    {
    }

    public function toString(): string
    {
        return $this->id;
    }

    public static function fromString(string $aggregateRootId): static
    {
        return new static($aggregateRootId);
    }
}

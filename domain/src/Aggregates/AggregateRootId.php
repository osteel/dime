<?php

namespace Domain\Aggregates;

use EventSauce\EventSourcing\AggregateRootId as AggregateRootIdInterface;
use Illuminate\Support\Str;

abstract class AggregateRootId implements AggregateRootIdInterface
{
    private function __construct(public readonly string $id)
    {
    }

    public static function generate(): static
    {
        return new static(Str::uuid());
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

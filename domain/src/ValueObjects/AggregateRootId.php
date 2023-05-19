<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use EventSauce\EventSourcing\AggregateRootId as AggregateRootIdInterface;
use Ramsey\Uuid\Uuid;

abstract readonly class AggregateRootId implements AggregateRootIdInterface
{
    final private function __construct(protected readonly string $id)
    {
    }

    public static function generate(): static
    {
        return new static(Uuid::uuid4()->toString());
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

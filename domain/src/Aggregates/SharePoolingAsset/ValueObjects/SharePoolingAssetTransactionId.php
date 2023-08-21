<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\ValueObjects;

use Ramsey\Uuid\Uuid;
use Stringable;

final readonly class SharePoolingAssetTransactionId implements Stringable
{
    final private function __construct(public string $id)
    {
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function __toString(): string
    {
        return $this->id;
    }
}

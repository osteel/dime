<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Entities\Exceptions;

use Domain\ValueObjects\Quantity;
use RuntimeException;

final class SharePoolingAssetDisposalException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function excessiveQuantityAllocated(Quantity $available, Quantity $allocated): self
    {
        return new self(sprintf('Allocated quantity %s exceeds available quantity %s', $allocated, $available));
    }
}

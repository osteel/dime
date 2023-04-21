<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Entities\Exceptions;

use Domain\ValueObjects\Quantity;
use RuntimeException;

final class SharePoolingAssetAcquisitionException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function excessiveQuantityAllocated(Quantity $available, Quantity $allocated): self
    {
        return new self(sprintf('The allocated quantity %s exceeds the available quantity %s', $allocated, $available));
    }

    public static function insufficientSameDayQuantity(Quantity $quantity, Quantity $sameDayQuantity): self
    {
        return new self(sprintf(
            'Cannot decrease same-day quantity by %s: only %s available',
            $quantity,
            $sameDayQuantity,
        ));
    }

    public static function insufficientThirtyDayQuantity(Quantity $quantity, Quantity $thirtyDayQuantity): self
    {
        return new self(sprintf(
            'Cannot decrease 30-day quantity by %s: only %s available',
            $quantity,
            $thirtyDayQuantity,
        ));
    }
}

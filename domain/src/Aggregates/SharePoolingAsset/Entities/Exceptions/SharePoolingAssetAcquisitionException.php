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

    public static function insufficientSameDayQuantityToIncrease(Quantity $quantity, Quantity $availableQuantity): self
    {
        return self::insufficientQuantity($quantity, $availableQuantity, 'same-day', 'increase');
    }

    public static function insufficientSameDayQuantityToDecrease(Quantity $quantity, Quantity $availableQuantity): self
    {
        return self::insufficientQuantity($quantity, $availableQuantity, 'same-day', 'decrease');
    }

    public static function insufficientThirtyDayQuantityToIncrease(Quantity $quantity, Quantity $availableQuantity): self
    {
        return self::insufficientQuantity($quantity, $availableQuantity, '30-day', 'increase');
    }

    public static function insufficientThirtyDayQuantityToDecrease(Quantity $quantity, Quantity $availableQuantity): self
    {
        return self::insufficientQuantity($quantity, $availableQuantity, '30-day', 'decrease');
    }

    private static function insufficientQuantity(Quantity $quantity, Quantity $availableQuantity, string $type, string $action): self
    {
        return new self(sprintf(
            'Cannot %s %s quantity by %s: only %s available',
            $action,
            $type,
            $quantity,
            $availableQuantity,
        ));
    }
}

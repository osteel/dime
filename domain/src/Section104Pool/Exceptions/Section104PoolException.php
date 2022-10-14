<?php

namespace Domain\Section104Pool\Exceptions;

use Domain\Enums\Currency;
use Domain\Section104Pool\Section104PoolId;
use RuntimeException;

final class Section104PoolException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function cannotAcquireDifferentCostBasisCurrency(
        Section104PoolId $section104PoolId,
        Currency $from,
        Currency $to
    ): self {
        return new self(sprintf(
            'Cannot acquire more of section 104 pool %s tokens because the currencies don\'t match (from %s to %s)',
            $section104PoolId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function disposalQuantityIsTooHigh(
        Section104PoolId $section104PoolId,
        string $disposalQuantity,
        string $availableQuantity
    ): self {
        return new self(sprintf(
            'Cannot dispose of %s section 104 pool %s tokens: %s available',
            $section104PoolId->toString(),
            $disposalQuantity,
            $availableQuantity,
        ));
    }
}

<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Exceptions;

use Domain\Enums\FiatCurrency;
use Domain\Aggregates\SharePooling\SharePoolingId;
use Domain\ValueObjects\Quantity;
use RuntimeException;

final class SharePoolingException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function cannotAcquireFromDifferentCurrency(
        SharePoolingId $sharePoolingId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot acquire more of section 104 pool %s tokens because the currencies don\'t match (from %s to %s)',
            $sharePoolingId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function cannotDisposeOfFromDifferentCurrency(
        SharePoolingId $sharePoolingId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot dispose of section 104 pool %s tokens because the currencies don\'t match (from %s to %s)',
            $sharePoolingId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function insufficientQuantity(
        SharePoolingId $sharePoolingId,
        Quantity $disposalQuantity,
        Quantity $availableQuantity
    ): self {
        return new self(sprintf(
            'Trying to dispose of %s section 104 pool %s tokens but only %s are available',
            $disposalQuantity,
            $sharePoolingId->toString(),
            $availableQuantity,
        ));
    }
}

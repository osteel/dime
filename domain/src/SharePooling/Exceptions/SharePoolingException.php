<?php

namespace Domain\SharePooling\Exceptions;

use Domain\Enums\FiatCurrency;
use Domain\SharePooling\SharePoolingId;
use Domain\SharePooling\ValueObjects\SharePoolingTransaction;
use Domain\SharePooling\ValueObjects\SharePoolingTransactions;
use RuntimeException;

final class SharePoolingException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function cannotAcquireFromDifferentFiatCurrency(
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

    public static function cannotDisposeOfFromDifferentFiatCurrency(
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

    public static function insufficientQuantityAvailable(
        SharePoolingId $sharePoolingId,
        string $disposalQuantity,
        string $availableQuantity
    ): self {
        return new self(sprintf(
            'Tried to dispose of %s section 104 pool %s tokens but only %s are available',
            $disposalQuantity,
            $sharePoolingId->toString(),
            $availableQuantity,
        ));
    }
}

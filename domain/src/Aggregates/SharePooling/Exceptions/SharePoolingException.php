<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Exceptions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePooling\SharePoolingId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Quantity;
use RuntimeException;
use Stringable;

final class SharePoolingException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function cannotAcquireFromDifferentCurrency(
        SharePoolingId $sharePoolingId,
        ?FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot acquire more %s tokens because the currencies don\'t match (from %s to %s)',
            $sharePoolingId->toString(),
            $from?->name() ?? 'undefined',
            $to->name(),
        ));
    }

    public static function cannotDisposeOfFromDifferentCurrency(
        SharePoolingId $sharePoolingId,
        ?FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot dispose of %s tokens because the currencies don\'t match (from %s to %s)',
            $sharePoolingId->toString(),
            $from?->name() ?? 'undefined',
            $to->name(),
        ));
    }

    public static function olderThanPreviousTransaction(
        SharePoolingId $sharePoolingId,
        Stringable $action,
        LocalDate $previousTransactionDate,
    ): self {
        return new self(sprintf(
            'This %s share pooling token transaction appears to be older than the previous one (%s): %s',
            $sharePoolingId->toString(),
            (string) $previousTransactionDate,
            (string) $action,
        ));
    }

    public static function insufficientQuantity(
        SharePoolingId $sharePoolingId,
        Quantity $disposalQuantity,
        Quantity $availableQuantity
    ): self {
        return new self(sprintf(
            'Trying to dispose of %s %s tokens but only %s are available',
            $disposalQuantity,
            $sharePoolingId->toString(),
            $availableQuantity,
        ));
    }
}

<?php

namespace Domain\Section104Pool\Exceptions;

use Domain\Enums\FiatCurrency;
use Domain\Section104Pool\Section104PoolId;
use Domain\Section104Pool\ValueObjects\Section104PoolTransaction;
use Domain\Section104Pool\ValueObjects\Section104PoolTransactions;
use RuntimeException;

final class Section104PoolException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function cannotAcquireFromDifferentFiatCurrency(
        Section104PoolId $section104PoolId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot acquire more of section 104 pool %s tokens because the currencies don\'t match (from %s to %s)',
            $section104PoolId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function cannotDisposeOfFromDifferentFiatCurrency(
        Section104PoolId $section104PoolId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot dispose of section 104 pool %s tokens because the currencies don\'t match (from %s to %s)',
            $section104PoolId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function insufficientQuantityAvailable(
        Section104PoolId $section104PoolId,
        string $disposalQuantity,
        string $availableQuantity
    ): self {
        return new self(sprintf(
            'Tried to dispose of %s section 104 pool %s tokens but only %s are available',
            $disposalQuantity,
            $section104PoolId->toString(),
            $availableQuantity,
        ));
    }

    public static function multipleSameDayDisposalsDetected(
        Section104PoolId $section104PoolId,
        Section104PoolTransactions $disposals,
    ): self {
        return new self(sprintf(
            'Multiple same-day section 104 pool %s disposals detected although same-day disposals should be consolidated: %s',
            $section104PoolId->toString(),
            print_r(array_map(fn (Section104PoolTransaction $transaction) => $transaction->__toString(), (array) $disposals), true),
        ));
    }
}

<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Exceptions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\Contracts\WithAsset;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use RuntimeException;
use Stringable;

final class NonFungibleAssetException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function alreadyAcquired(Asset $asset): self
    {
        return new self(sprintf('Non-fungible asset %s has already been acquired', (string) $asset));
    }

    public static function olderThanPreviousTransaction(
        Stringable&WithAsset $action,
        LocalDate $previousTransactionDate,
    ): self {
        return new self(sprintf(
            'This non-fungible asset %s transaction appears to be older than the previous one (%s): %s',
            (string) $action->getAsset(),
            (string) $previousTransactionDate,
            (string) $action,
        ));
    }

    public static function currencyMismatch(
        Stringable&WithAsset $action,
        ?FiatCurrency $current,
        FiatCurrency $incoming,
    ): self {
        return new self(sprintf(
            'Cannot process this %s non-fungible asset transaction because the currencies don\'t match (current: %s; incoming: %s): %s',
            (string) $action->getAsset(),
            $current?->name() ?? 'undefined',
            $incoming->name(),
            (string) $action,
        ));
    }

    public static function cannotIncreaseCostBasisBeforeAcquisition(Asset $asset): self
    {
        return new self(sprintf(
            'Cannot increase the cost basis of non-fungible asset %s as it has not been acquired',
            (string) $asset,
        ));
    }

    public static function cannotDisposeOfBeforeAcquisition(Asset $asset): self
    {
        return new self(sprintf('Cannot dispose of non-fungible asset %s as it has not been acquired', (string) $asset));
    }
}

<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Exceptions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\Contracts\WithAsset;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Quantity;
use RuntimeException;
use Stringable;

final class SharePoolingAssetException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function currencyMismatch(
        Stringable&WithAsset $action,
        ?FiatCurrency $current,
        FiatCurrency $incoming,
    ): self {
        return new self(sprintf(
            'Cannot process this share pooling asset %s transaction because the currencies don\'t match (current: %s; incoming: %s): %s',
            $action->getAsset(),
            $current?->name() ?? 'undefined',
            $incoming->name(),
            $action,
        ));
    }

    public static function olderThanPreviousTransaction(
        Stringable&WithAsset $action,
        LocalDate $previousTransactionDate,
    ): self {
        return new self(sprintf(
            'This share pooling asset %s transaction appears to be older than the previous one (%s): %s',
            $action->getAsset(),
            $previousTransactionDate,
            $action,
        ));
    }

    public static function insufficientQuantity(
        Asset $asset,
        Quantity $disposalQuantity,
        Quantity $availableQuantity,
    ): self {
        return new self(sprintf(
            'Trying to dispose of %s %s tokens but only %s are available',
            $disposalQuantity,
            $asset,
            $availableQuantity,
        ));
    }
}

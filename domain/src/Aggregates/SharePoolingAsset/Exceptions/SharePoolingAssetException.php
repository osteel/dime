<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Exceptions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
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

    public static function assetMismatch(
        SharePoolingAssetId $sharePoolingAssetId,
        Stringable $action,
        ?Asset $current,
        Asset $incoming,
    ): self {
        return new self(sprintf(
            'Cannot process this %s share pooling asset transaction because the assets don\'t match (current: %s; incoming: %s): %s',
            $sharePoolingAssetId->toString(),
            (string) $current ?: 'undefined',
            (string) $incoming,
            (string) $action,
        ));
    }

    public static function currencyMismatch(
        SharePoolingAssetId $sharePoolingAssetId,
        Stringable $action,
        ?FiatCurrency $current,
        FiatCurrency $incoming,
    ): self {
        return new self(sprintf(
            'Cannot process this %s share pooling asset transaction because the currencies don\'t match (current: %s; incoming: %s): %s',
            $sharePoolingAssetId->toString(),
            $current?->name() ?? 'undefined',
            $incoming->name(),
            (string) $action,
        ));
    }

    public static function olderThanPreviousTransaction(
        SharePoolingAssetId $sharePoolingAssetId,
        Stringable $action,
        LocalDate $previousTransactionDate,
    ): self {
        return new self(sprintf(
            'This %s share pooling asset transaction appears to be older than the previous one (%s): %s',
            $sharePoolingAssetId->toString(),
            (string) $previousTransactionDate,
            (string) $action,
        ));
    }

    public static function insufficientQuantity(
        SharePoolingAssetId $sharePoolingAssetId,
        Quantity $disposalQuantity,
        Quantity $availableQuantity,
    ): self {
        return new self(sprintf(
            'Trying to dispose of %s %s tokens but only %s are available',
            $disposalQuantity,
            $sharePoolingAssetId->toString(),
            $availableQuantity,
        ));
    }
}

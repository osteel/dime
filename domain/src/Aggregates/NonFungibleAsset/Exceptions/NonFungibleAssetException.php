<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Exceptions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use Domain\Enums\FiatCurrency;
use RuntimeException;
use Stringable;

final class NonFungibleAssetException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function alreadyAcquired(NonFungibleAssetId $nonFungibleAssetId): self
    {
        return new self(sprintf('Non-fungible asset %s has already been acquired', $nonFungibleAssetId->toString()));
    }

    public static function olderThanPreviousTransaction(
        NonFungibleAssetId $nonFungibleAssetId,
        Stringable $action,
        LocalDate $previousTransactionDate,
    ): self {
        return new self(sprintf(
            'This non-fungible asset %s transaction appears to be older than the previous one (%s): %s',
            $nonFungibleAssetId->toString(),
            (string) $previousTransactionDate,
            (string) $action,
        ));
    }

    public static function cannotIncreaseCostBasisBeforeAcquisition(NonFungibleAssetId $nonFungibleAssetId): self
    {
        return new self(sprintf(
            'Cannot increase the cost basis of non-fungible asset %s as it has not been acquired',
            $nonFungibleAssetId->toString(),
        ));
    }

    public static function cannotIncreaseCostBasisFromDifferentCurrency(
        NonFungibleAssetId $nonFungibleAssetId,
        FiatCurrency $current,
        FiatCurrency $incoming,
    ): self {
        return new self(sprintf(
            'Cannot increase the cost basis of non-fungible asset %s because the currencies don\'t match (current: %s; incoming: %s)',
            $nonFungibleAssetId->toString(),
            $current->name(),
            $incoming->name(),
        ));
    }

    public static function cannotDisposeOfBeforeAcquisition(NonFungibleAssetId $nonFungibleAssetId): self
    {
        return new self(sprintf(
            'Cannot dispose of non-fungible asset %s as it has not been acquired',
            $nonFungibleAssetId->toString(),
        ));
    }
}

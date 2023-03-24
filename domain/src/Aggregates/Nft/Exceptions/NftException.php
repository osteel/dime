<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft\Exceptions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\Nft\NftId;
use Domain\Enums\FiatCurrency;
use RuntimeException;
use Stringable;

final class NftException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function alreadyAcquired(NftId $nftId): self
    {
        return new self(sprintf('NFT %s has already been acquired', $nftId->toString()));
    }

    public static function olderThanPreviousTransaction(
        NftId $nftId,
        Stringable $action,
        LocalDate $previousTransactionDate,
    ): self {
        return new self(sprintf(
            'This NFT %s transaction appears to be older than the previous one (%s): %s',
            $nftId->toString(),
            (string) $previousTransactionDate,
            (string) $action,
        ));
    }

    public static function cannotIncreaseCostBasisBeforeAcquisition(NftId $nftId): self
    {
        return new self(sprintf(
            'Cannot increase the cost basis of NFT %s as it has not been acquired',
            $nftId->toString(),
        ));
    }

    public static function cannotIncreaseCostBasisFromDifferentCurrency(
        NftId $nftId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot increase the cost basis of NFT %s because the currencies don\'t match (from %s to %s)',
            $nftId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function cannotDisposeOfBeforeAcquisition(NftId $nftId): self
    {
        return new self(sprintf(
            'Cannot dispose of NFT %s as it has not been acquired',
            $nftId->toString(),
        ));
    }
}

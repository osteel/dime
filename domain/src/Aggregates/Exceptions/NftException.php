<?php

namespace Domain\Aggregates\Exceptions;

use Domain\Aggregates\NftId;
use Domain\Enums\Currency;
use RuntimeException;

final class NftException extends RuntimeException
{
    private function __construct(string $message)
    {
        return parent::__construct($message);
    }

    public static function alreadyAcquired(NftId $nftId): self
    {
        return new self(sprintf('NFT %s has already been acquired', $nftId->toString()));
    }

    public static function cannotIncreaseCostBasisBeforeAcquisition(NftId $nftId): self
    {
        return new self(sprintf(
            'Cannot increase the cost basis of NFT %s as it has not been acquired',
            $nftId->toString(),
        ));
    }

    public static function cannotIncreaseCostBasisFromDifferentCurrency(NftId $nftId, Currency $from, Currency $to): self
    {
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

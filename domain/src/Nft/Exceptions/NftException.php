<?php

namespace Domain\Nft\Exceptions;

use Domain\Nft\NftId;
use Domain\Enums\FiatCurrency;
use RuntimeException;

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

    public static function cannotIncreaseCostBasisBeforeAcquisition(NftId $nftId): self
    {
        return new self(sprintf(
            'Cannot increase the cost basis of NFT %s as it has not been acquired',
            $nftId->toString(),
        ));
    }

    public static function cannotIncreaseCostBasisFromDifferentFiatCurrency(
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

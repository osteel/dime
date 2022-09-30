<?php

namespace Domain\Aggregates\Exceptions;

use Domain\Aggregates\NftId;
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

    public static function cannotAverageCostBasisBeforeAcquisition(NftId $nftId): self
    {
        return new self(sprintf(
            'Cannot average the cost basis of NFT %s as it has not been acquired yet',
            $nftId->toString(),
        ));
    }
}

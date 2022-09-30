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
}

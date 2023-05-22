<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\ValueObjects\Exceptions;

use Domain\ValueObjects\Asset;
use RuntimeException;

final class NonFungibleAssetIdException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function assetIsFungible(Asset $asset): self
    {
        return new self(sprintf('Cannot create non-fungible asset ID from fungible asset %s', (string) $asset));
    }
}

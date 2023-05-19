<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions;

use Domain\ValueObjects\Asset;
use RuntimeException;

final class SharePoolingAssetIdException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function assetIsNonFungible(Asset $asset): self
    {
        return new self(sprintf('Cannot create share pooling asset ID from non-fungible asset %s', (string) $asset));
    }
}

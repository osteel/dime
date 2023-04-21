<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Entities\Exceptions;

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\Quantity;
use RuntimeException;

final class SharePoolingAssetDisposalException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function currencyMismatch(FiatCurrency $costBasis, FiatCurrency $proceeds): self
    {
        return new self(sprintf(
            'The currencies don\'t match (cost basis in %s, proceeds in %s)',
            $costBasis->name(),
            $proceeds->name(),
        ));
    }

    public static function excessiveQuantityAllocated(Quantity $available, Quantity $allocated): self
    {
        return new self(sprintf('The allocated quantity %s exceeds the available quantity %s', $allocated, $available));
    }
}

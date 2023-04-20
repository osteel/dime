<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster\Exceptions;

use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use RuntimeException;

final class QuantityAdjusterException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function transactionNotFound(SharePoolingAssetTransactionId $id): self
    {
        return new self(sprintf('Transaction %s not found', (string) $id));
    }

    public static function notAnAcquisition(SharePoolingAssetTransactionId $id): self
    {
        return new self(sprintf('Transaction %s is not an acquisition', (string) $id));
    }
}

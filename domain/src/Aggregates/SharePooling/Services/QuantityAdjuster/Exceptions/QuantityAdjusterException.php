<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePooling\Services\QuantityAdjuster\Exceptions;

use RuntimeException;

final class QuantityAdjusterException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function transactionNotFound(int $position): self
    {
        return new self(sprintf('No transaction at position %s', $position));
    }

    public static function notAnAcquisition(int $position): self
    {
        return new self(sprintf('Transaction at position %s is not an acquisition', $position));
    }
}

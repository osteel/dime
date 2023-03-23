<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Transactions\Exceptions;

use Domain\ValueObjects\Transactions\Acquisition;
use RuntimeException;

final class AcquisitionException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function isFiat(Acquisition $transaction): self
    {
        return new self(sprintf('The acquired asset cannot be fiat: %s', (string) $transaction));
    }
}

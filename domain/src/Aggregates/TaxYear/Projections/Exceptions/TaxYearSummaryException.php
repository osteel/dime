<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Projections\Exceptions;

use RuntimeException;

final class TaxYearSummaryException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidCapitalGainValues(?string $value = ''): self
    {
        return new self(sprintf('Could not parse capital gain %s', $value));
    }
}

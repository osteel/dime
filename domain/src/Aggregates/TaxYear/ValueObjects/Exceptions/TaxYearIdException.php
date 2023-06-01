<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\ValueObjects\Exceptions;

use RuntimeException;

final class TaxYearIdException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidTaxYear(): self
    {
        return new self('Tax years must be two consecutive years separated by an hyphen (e.g. "2021-2022")');
    }
}

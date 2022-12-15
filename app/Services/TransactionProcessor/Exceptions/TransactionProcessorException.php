<?php

declare(strict_types=1);

namespace App\Services\TransactionProcessor\Exceptions;

use RuntimeException;

final class TransactionProcessorException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function cannotParseDate(string $value): self
    {
        return new self(sprintf('Could not parse date from value %s', $value));
    }
}

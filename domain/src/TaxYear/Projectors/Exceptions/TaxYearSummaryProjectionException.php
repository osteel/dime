<?php

declare(strict_types=1);

namespace Domain\TaxYear\Projectors\Exceptions;

use RuntimeException;

final class TaxYearSummaryProjectionException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function missingTaxYearId(string $eventClass): self
    {
        return new self(sprintf('Event of type %s was caught without a tax year ID', $eventClass));
    }
}

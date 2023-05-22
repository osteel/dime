<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Exceptions;

use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Enums\FiatCurrency;
use RuntimeException;
use Stringable;

final class TaxYearException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function currencyMismatch(
        TaxYearId $taxYearId,
        Stringable $action,
        ?FiatCurrency $current,
        FiatCurrency $incoming,
    ): self {
        return new self(sprintf(
            'Cannot process this %s tax year action because the currencies don\'t match (current: %s; incoming: %s): %s',
            $taxYearId->toString(),
            $current?->name() ?? 'undefined',
            $incoming->name(),
            $action,
        ));
    }

    public static function cannotRevertCapitalGainUpdateBeforeCapitalGainIsUpdated(TaxYearId $taxYearId): self
    {
        return new self(sprintf(
            'Cannot revert capital gain update for tax year %s because the capital gain has not been updated yet',
            $taxYearId->toString(),
        ));
    }
}

<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Exceptions;

use Domain\Enums\FiatCurrency;
use Domain\Aggregates\TaxYear\TaxYearId;
use RuntimeException;

final class TaxYearException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function cannotUpdateCapitalGainFromDifferentCurrency(
        TaxYearId $taxYearId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot update capital gain for tax year %s because the currencies don\'t match (from %s to %s)',
            $taxYearId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function cannotRevertCapitalGainUpdateBeforeCapitalGainIsUpdated(TaxYearId $taxYearId): self
    {
        return new self(sprintf(
            'Cannot revert capital gain update for tax year %s because the capital gain has not been updated yet',
            $taxYearId->toString(),
        ));
    }

    public static function cannotRevertCapitalGainUpdateFromDifferentCurrency(
        TaxYearId $taxYearId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot revert capital gain update for tax year %s because the currencies don\'t match (from %s to %s)',
            $taxYearId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function cannotUpdateIncomeFromDifferentCurrency(
        TaxYearId $taxYearId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot update the income for tax year %s because the currencies don\'t match (from %s to %s)',
            $taxYearId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function cannotUpdateNonAttributableAllowableCostFromDifferentCurrency(
        TaxYearId $taxYearId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot update the non-attributable allowable cost for tax year %s because the currencies don\'t match (from %s to %s)',
            $taxYearId->toString(),
            $from->name(),
            $to->name(),
        ));
    }
}

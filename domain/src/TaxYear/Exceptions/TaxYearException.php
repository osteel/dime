<?php

declare(strict_types=1);

namespace Domain\TaxYear\Exceptions;

use Domain\Enums\FiatCurrency;
use Domain\TaxYear\TaxYearId;
use RuntimeException;

final class TaxYearException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function cannotRecordCapitalGainForDifferentCurrency(
        TaxYearId $taxYearId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot record capital gain for tax year %s because the currencies don\'t match (from %s to %s)',
            $taxYearId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function cannotRevertCapitalGainBeforeCapitalGainIsRecorded(TaxYearId $taxYearId): self
    {
        return new self(sprintf(
            'Cannot revert capital gain for tax year %s because no capital gain or loss was recorded yet',
            $taxYearId->toString(),
        ));
    }

    public static function cannotRevertCapitalGainFromDifferentCurrency(
        TaxYearId $taxYearId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot revert capital gain for tax year %s because the currencies don\'t match (from %s to %s)',
            $taxYearId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function cannotRecordCapitalLossForDifferentCurrency(
        TaxYearId $taxYearId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot record capital loss for tax year %s because the currencies don\'t match (from %s to %s)',
            $taxYearId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function cannotRevertCapitalLossBeforeCapitalLossIsRecorded(TaxYearId $taxYearId): self
    {
        return new self(sprintf(
            'Cannot revert capital loss for tax year %s because no capital gain or loss was recorded yet',
            $taxYearId->toString(),
        ));
    }

    public static function cannotRevertCapitalLossFromDifferentCurrency(
        TaxYearId $taxYearId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot revert capital loss for tax year %s because the currencies don\'t match (from %s to %s)',
            $taxYearId->toString(),
            $from->name(),
            $to->name(),
        ));
    }

    public static function cannotRecordIncomeFromDifferentCurrency(
        TaxYearId $taxYearId,
        FiatCurrency $from,
        FiatCurrency $to
    ): self {
        return new self(sprintf(
            'Cannot record some income for tax year %s because the currencies don\'t match (from %s to %s)',
            $taxYearId->toString(),
            $from->name(),
            $to->name(),
        ));
    }
}

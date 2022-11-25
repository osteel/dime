<?php

declare(strict_types=1);

namespace Domain\TaxYear\Exceptions;

use Domain\Enums\FiatCurrency;
use Domain\TaxYear\TaxYearId;
use Domain\ValueObjects\FiatAmount;
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
            'Cannot revert capital gain for tax year %s because no capital gain was recorded yet',
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

    public static function cannotRevertCapitalGainBecauseAmountIsTooHigh(
        TaxYearId $taxYearId,
        FiatAmount $amountToRevert,
        FiatAmount $availableAmount
    ): self {
        return new self(sprintf(
            'Trying to revert capital gain of %s for tax year %s but only %s is available',
            $amountToRevert,
            $taxYearId->toString(),
            $availableAmount,
        ));
    }
}

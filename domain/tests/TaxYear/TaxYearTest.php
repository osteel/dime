<?php

use Domain\Enums\FiatCurrency;
use Domain\TaxYear\Actions\RecordCapitalGain;
use Domain\TaxYear\Actions\RevertCapitalGain;
use Domain\TaxYear\Events\CapitalGainRecorded;
use Domain\TaxYear\Events\CapitalGainReverted;
use Domain\TaxYear\Exceptions\TaxYearException;
use Domain\Tests\TaxYear\TaxYearTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(TaxYearTestCase::class);

beforeEach(function () {
    $this->taxYearId = $this->aggregateRootId();
});

it('can record a capital gain', function () {
    $recordCapitalGain = new RecordCapitalGain(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $capitalGainRecorded = new CapitalGainRecorded(
        taxYearId: $recordCapitalGain->taxYearId,
        amount: $recordCapitalGain->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($recordCapitalGain)
        ->then($capitalGainRecorded);
});

it('cannot record a capital gain because the currency is different', function () {
    $capitalGainRecorded = new CapitalGainRecorded(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $recordCapitalGain = new RecordCapitalGain(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotRecordCapitalGain = TaxYearException::cannotRecordCapitalGainForDifferentCurrency(
        taxYearId: $recordCapitalGain->taxYearId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainRecorded)
        ->when($recordCapitalGain)
        ->expectToFail($cannotRecordCapitalGain);
});

it('can revert a capital gain', function () {
    $capitalGainRecorded = new CapitalGainRecorded(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $revertCapitalGain = new RevertCapitalGain(
        taxYearId: $capitalGainRecorded->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $capitalGainReverted = new CapitalGainReverted(
        taxYearId: $capitalGainRecorded->taxYearId,
        amount: $revertCapitalGain->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainRecorded)
        ->when($revertCapitalGain)
        ->then($capitalGainReverted);
});

it('cannot revert a capital gain before a capital gain was recorded', function () {
    $revertCapitalGain = new RevertCapitalGain(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotRevertCapitalGain = TaxYearException::cannotRevertCapitalGainBeforeCapitalGainIsRecorded(
        taxYearId: $revertCapitalGain->taxYearId,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($revertCapitalGain)
        ->expectToFail($cannotRevertCapitalGain);
});

it('cannot revert a capital gain because the currency is different', function () {
    $capitalGainRecorded = new CapitalGainRecorded(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $revertCapitalGain = new RevertCapitalGain(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotRevertCapitalGain = TaxYearException::cannotRevertCapitalGainFromDifferentCurrency(
        taxYearId: $revertCapitalGain->taxYearId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainRecorded)
        ->when($revertCapitalGain)
        ->expectToFail($cannotRevertCapitalGain);
});

it('cannot revert a capital gain because the amount is too high', function () {
    $capitalGainRecorded = new CapitalGainRecorded(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('50', FiatCurrency::GBP),
    );

    $revertCapitalGain = new RevertCapitalGain(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $cannotRevertCapitalGain = TaxYearException::cannotRevertCapitalGainBecauseAmountIsTooHigh(
        taxYearId: $revertCapitalGain->taxYearId,
        amountToRevert: $revertCapitalGain->amount,
        availableAmount: $capitalGainRecorded->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainRecorded)
        ->when($revertCapitalGain)
        ->expectToFail($cannotRevertCapitalGain);
});

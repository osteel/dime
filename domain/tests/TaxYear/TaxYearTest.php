<?php

use Domain\Enums\FiatCurrency;
use Domain\TaxYear\Actions\RecordCapitalGain;
use Domain\TaxYear\Actions\RecordCapitalLoss;
use Domain\TaxYear\Actions\RecordIncome;
use Domain\TaxYear\Actions\RevertCapitalGain;
use Domain\TaxYear\Actions\RevertCapitalLoss;
use Domain\TaxYear\Events\CapitalGainRecorded;
use Domain\TaxYear\Events\CapitalGainReverted;
use Domain\TaxYear\Events\CapitalLossRecorded;
use Domain\TaxYear\Events\CapitalLossReverted;
use Domain\TaxYear\Events\IncomeRecorded;
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

it('can record a capital loss', function () {
    $recordCapitalLoss = new RecordCapitalLoss(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $capitalLossRecorded = new CapitalLossRecorded(
        taxYearId: $recordCapitalLoss->taxYearId,
        amount: $recordCapitalLoss->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($recordCapitalLoss)
        ->then($capitalLossRecorded);
});

it('cannot record a capital loss because the currency is different', function () {
    $capitalLossRecorded = new CapitalLossRecorded(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $recordCapitalLoss = new RecordCapitalLoss(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotRecordCapitalLoss = TaxYearException::cannotRecordCapitalLossForDifferentCurrency(
        taxYearId: $recordCapitalLoss->taxYearId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalLossRecorded)
        ->when($recordCapitalLoss)
        ->expectToFail($cannotRecordCapitalLoss);
});

it('can revert a capital loss', function () {
    $capitalLossRecorded = new CapitalLossRecorded(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $revertCapitalLoss = new RevertCapitalLoss(
        taxYearId: $capitalLossRecorded->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $capitalLossReverted = new CapitalLossReverted(
        taxYearId: $capitalLossRecorded->taxYearId,
        amount: $revertCapitalLoss->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalLossRecorded)
        ->when($revertCapitalLoss)
        ->then($capitalLossReverted);
});

it('cannot revert a capital loss before a capital loss was recorded', function () {
    $revertCapitalLoss = new RevertCapitalLoss(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotRevertCapitalLoss = TaxYearException::cannotRevertCapitalLossBeforeCapitalLossIsRecorded(
        taxYearId: $revertCapitalLoss->taxYearId,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($revertCapitalLoss)
        ->expectToFail($cannotRevertCapitalLoss);
});

it('cannot revert a capital loss because the currency is different', function () {
    $capitalLossRecorded = new CapitalLossRecorded(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $revertCapitalLoss = new RevertCapitalLoss(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotRevertCapitalLoss = TaxYearException::cannotRevertCapitalLossFromDifferentCurrency(
        taxYearId: $revertCapitalLoss->taxYearId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalLossRecorded)
        ->when($revertCapitalLoss)
        ->expectToFail($cannotRevertCapitalLoss);
});

it('can record some income', function () {
    $recordIncome = new RecordIncome(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $incomeRecorded = new IncomeRecorded(
        taxYearId: $recordIncome->taxYearId,
        amount: $recordIncome->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($recordIncome)
        ->then($incomeRecorded);
});

it('cannot record some income because the currency is different', function () {
    $incomeRecorded = new IncomeRecorded(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $recordIncome = new RecordIncome(
        taxYearId: $this->taxYearId,
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotRecordIncome = TaxYearException::cannotRecordIncomeFromDifferentCurrency(
        taxYearId: $recordIncome->taxYearId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($incomeRecorded)
        ->when($recordIncome)
        ->expectToFail($cannotRecordIncome);
});

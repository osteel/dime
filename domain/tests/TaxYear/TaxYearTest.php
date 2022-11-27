<?php

use Domain\Enums\FiatCurrency;
use Domain\TaxYear\Actions\RecordCapitalGain;
use Domain\TaxYear\Actions\RecordCapitalLoss;
use Domain\TaxYear\Actions\RecordIncome;
use Domain\TaxYear\Actions\RecordNonAttributableAllowableCost;
use Domain\TaxYear\Actions\RevertCapitalGain;
use Domain\TaxYear\Actions\RevertCapitalLoss;
use Domain\TaxYear\Events\CapitalGainRecorded;
use Domain\TaxYear\Events\CapitalGainReverted;
use Domain\TaxYear\Events\CapitalLossRecorded;
use Domain\TaxYear\Events\CapitalLossReverted;
use Domain\TaxYear\Events\IncomeRecorded;
use Domain\TaxYear\Events\NonAttributableAllowableCostRecorded;
use Domain\TaxYear\Exceptions\TaxYearException;
use Domain\Tests\TaxYear\TaxYearTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(TaxYearTestCase::class);

it('can record a capital gain', function () {
    $recordCapitalGain = new RecordCapitalGain(amount: new FiatAmount('100', FiatCurrency::GBP));
    $capitalGainRecorded = new CapitalGainRecorded(amount: $recordCapitalGain->amount);

    /** @var AggregateRootTestCase $this */
    $this->when($recordCapitalGain)
        ->then($capitalGainRecorded);
});

it('cannot record a capital gain because the currency is different', function () {
    $capitalGainRecorded = new CapitalGainRecorded(amount: new FiatAmount('100', FiatCurrency::GBP));
    $recordCapitalGain = new RecordCapitalGain(amount: new FiatAmount('100', FiatCurrency::EUR));

    $cannotRecordCapitalGain = TaxYearException::cannotRecordCapitalGainForDifferentCurrency(
        taxYearId: $this->aggregateRootId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainRecorded)
        ->when($recordCapitalGain)
        ->expectToFail($cannotRecordCapitalGain);
});

it('can revert a capital gain', function () {
    $capitalGainRecorded = new CapitalGainRecorded(amount: new FiatAmount('100', FiatCurrency::GBP));
    $revertCapitalGain = new RevertCapitalGain(amount: new FiatAmount('100', FiatCurrency::GBP));
    $capitalGainReverted = new CapitalGainReverted(amount: $revertCapitalGain->amount);

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainRecorded)
        ->when($revertCapitalGain)
        ->then($capitalGainReverted);
});

it('cannot revert a capital gain before a capital gain was recorded', function () {
    $revertCapitalGain = new RevertCapitalGain(amount: new FiatAmount('100', FiatCurrency::EUR));
    $cannotRevertCapitalGain = TaxYearException::cannotRevertCapitalGainBeforeCapitalGainIsRecorded(
        taxYearId: $this->aggregateRootId,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($revertCapitalGain)
        ->expectToFail($cannotRevertCapitalGain);
});

it('cannot revert a capital gain because the currency is different', function () {
    $capitalGainRecorded = new CapitalGainRecorded(amount: new FiatAmount('100', FiatCurrency::GBP));
    $revertCapitalGain = new RevertCapitalGain(amount: new FiatAmount('100', FiatCurrency::EUR));

    $cannotRevertCapitalGain = TaxYearException::cannotRevertCapitalGainFromDifferentCurrency(
        taxYearId: $this->aggregateRootId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainRecorded)
        ->when($revertCapitalGain)
        ->expectToFail($cannotRevertCapitalGain);
});

it('can record a capital loss', function () {
    $recordCapitalLoss = new RecordCapitalLoss(amount: new FiatAmount('100', FiatCurrency::GBP));
    $capitalLossRecorded = new CapitalLossRecorded(amount: $recordCapitalLoss->amount);

    /** @var AggregateRootTestCase $this */
    $this->when($recordCapitalLoss)
        ->then($capitalLossRecorded);
});

it('cannot record a capital loss because the currency is different', function () {
    $capitalLossRecorded = new CapitalLossRecorded(amount: new FiatAmount('100', FiatCurrency::GBP));
    $recordCapitalLoss = new RecordCapitalLoss(amount: new FiatAmount('100', FiatCurrency::EUR));

    $cannotRecordCapitalLoss = TaxYearException::cannotRecordCapitalLossForDifferentCurrency(
        taxYearId: $this->aggregateRootId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalLossRecorded)
        ->when($recordCapitalLoss)
        ->expectToFail($cannotRecordCapitalLoss);
});

it('can revert a capital loss', function () {
    $capitalLossRecorded = new CapitalLossRecorded(amount: new FiatAmount('100', FiatCurrency::GBP));
    $revertCapitalLoss = new RevertCapitalLoss(amount: new FiatAmount('100', FiatCurrency::GBP));
    $capitalLossReverted = new CapitalLossReverted(amount: $revertCapitalLoss->amount);

    /** @var AggregateRootTestCase $this */
    $this->given($capitalLossRecorded)
        ->when($revertCapitalLoss)
        ->then($capitalLossReverted);
});

it('cannot revert a capital loss before a capital loss was recorded', function () {
    $revertCapitalLoss = new RevertCapitalLoss(amount: new FiatAmount('100', FiatCurrency::EUR));
    $cannotRevertCapitalLoss = TaxYearException::cannotRevertCapitalLossBeforeCapitalLossIsRecorded(
        taxYearId: $this->aggregateRootId,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($revertCapitalLoss)
        ->expectToFail($cannotRevertCapitalLoss);
});

it('cannot revert a capital loss because the currency is different', function () {
    $capitalLossRecorded = new CapitalLossRecorded(amount: new FiatAmount('100', FiatCurrency::GBP));
    $revertCapitalLoss = new RevertCapitalLoss(amount: new FiatAmount('100', FiatCurrency::EUR));

    $cannotRevertCapitalLoss = TaxYearException::cannotRevertCapitalLossFromDifferentCurrency(
        taxYearId: $this->aggregateRootId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalLossRecorded)
        ->when($revertCapitalLoss)
        ->expectToFail($cannotRevertCapitalLoss);
});

it('can record some income', function () {
    $recordIncome = new RecordIncome(amount: new FiatAmount('100', FiatCurrency::GBP));
    $incomeRecorded = new IncomeRecorded(amount: $recordIncome->amount);

    /** @var AggregateRootTestCase $this */
    $this->when($recordIncome)
        ->then($incomeRecorded);
});

it('cannot record some income because the currency is different', function () {
    $incomeRecorded = new IncomeRecorded(amount: new FiatAmount('100', FiatCurrency::GBP));
    $recordIncome = new RecordIncome(amount: new FiatAmount('100', FiatCurrency::EUR));

    $cannotRecordIncome = TaxYearException::cannotRecordIncomeFromDifferentCurrency(
        taxYearId: $this->aggregateRootId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($incomeRecorded)
        ->when($recordIncome)
        ->expectToFail($cannotRecordIncome);
});

it('can record a non-attributable allowable cost', function () {
    $recordNonAttributableAllowableCost = new RecordNonAttributableAllowableCost(
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $nonAttributableAllowableCostRecorded = new NonAttributableAllowableCostRecorded(
        amount: $recordNonAttributableAllowableCost->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($recordNonAttributableAllowableCost)
        ->then($nonAttributableAllowableCostRecorded);
});

it('cannot record a non-attributable allowable cost because the currency is different', function () {
    $nonAttributableAllowableCostRecorded = new NonAttributableAllowableCostRecorded(
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $recordNonAttributableAllowableCost = new RecordNonAttributableAllowableCost(
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotRecordNonAttributableAllowableCost = TaxYearException::cannotRecordNonAttributableAllowableCostFromDifferentCurrency(
        taxYearId: $this->aggregateRootId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nonAttributableAllowableCostRecorded)
        ->when($recordNonAttributableAllowableCost)
        ->expectToFail($cannotRecordNonAttributableAllowableCost);
});

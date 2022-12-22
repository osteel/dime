<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalLoss;
use Domain\Aggregates\TaxYear\Actions\RecordIncome;
use Domain\Aggregates\TaxYear\Actions\RecordNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalLoss;
use Domain\Aggregates\TaxYear\Events\CapitalGainRecorded;
use Domain\Aggregates\TaxYear\Events\CapitalGainReverted;
use Domain\Aggregates\TaxYear\Events\CapitalLossRecorded;
use Domain\Aggregates\TaxYear\Events\CapitalLossReverted;
use Domain\Aggregates\TaxYear\Events\IncomeRecorded;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostRecorded;
use Domain\Aggregates\TaxYear\Exceptions\TaxYearException;
use Domain\Enums\FiatCurrency;
use Domain\Tests\Aggregates\TaxYear\TaxYearTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(TaxYearTestCase::class);

it('can record a capital gain', function () {
    $recordCapitalGain = new RecordCapitalGain(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $capitalGainRecorded = new CapitalGainRecorded(
        taxYear: $this->taxYear,
        date: $recordCapitalGain->date,
        amount: $recordCapitalGain->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($recordCapitalGain)
        ->then($capitalGainRecorded);
});

it('cannot record a capital gain because the currency is different', function () {
    $capitalGainRecorded = new CapitalGainRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $recordCapitalGain = new RecordCapitalGain(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

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
    $capitalGainRecorded = new CapitalGainRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $revertCapitalGain = new RevertCapitalGain(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $capitalGainReverted = new CapitalGainReverted(
        taxYear: $this->taxYear,
        date: $revertCapitalGain->date,
        amount: $revertCapitalGain->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainRecorded)
        ->when($revertCapitalGain)
        ->then($capitalGainReverted);
});

it('cannot revert a capital gain before a capital gain was recorded', function () {
    $revertCapitalGain = new RevertCapitalGain(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotRevertCapitalGain = TaxYearException::cannotRevertCapitalGainBeforeCapitalGainIsRecorded(
        taxYearId: $this->aggregateRootId,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($revertCapitalGain)
        ->expectToFail($cannotRevertCapitalGain);
});

it('cannot revert a capital gain because the currency is different', function () {
    $capitalGainRecorded = new CapitalGainRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $revertCapitalGain = new RevertCapitalGain(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

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
    $recordCapitalLoss = new RecordCapitalLoss(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $capitalLossRecorded = new CapitalLossRecorded(
        taxYear: $this->taxYear,
        date: $recordCapitalLoss->date,
        amount: $recordCapitalLoss->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($recordCapitalLoss)
        ->then($capitalLossRecorded);
});

it('cannot record a capital loss because the currency is different', function () {
    $capitalLossRecorded = new CapitalLossRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $recordCapitalLoss = new RecordCapitalLoss(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

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
    $capitalLossRecorded = new CapitalLossRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $revertCapitalLoss = new RevertCapitalLoss(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $capitalLossReverted = new CapitalLossReverted(
        taxYear: $this->taxYear,
        date: $revertCapitalLoss->date,
        amount: $revertCapitalLoss->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalLossRecorded)
        ->when($revertCapitalLoss)
        ->then($capitalLossReverted);
});

it('cannot revert a capital loss before a capital loss was recorded', function () {
    $revertCapitalLoss = new RevertCapitalLoss(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotRevertCapitalLoss = TaxYearException::cannotRevertCapitalLossBeforeCapitalLossIsRecorded(
        taxYearId: $this->aggregateRootId,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($revertCapitalLoss)
        ->expectToFail($cannotRevertCapitalLoss);
});

it('cannot revert a capital loss because the currency is different', function () {
    $capitalLossRecorded = new CapitalLossRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $revertCapitalLoss = new RevertCapitalLoss(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

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
    $recordIncome = new RecordIncome(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $incomeRecorded = new IncomeRecorded(
        taxYear: $this->taxYear,
        date: $recordIncome->date,
        amount: $recordIncome->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($recordIncome)
        ->then($incomeRecorded);
});

it('cannot record some income because the currency is different', function () {
    $incomeRecorded = new IncomeRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $recordIncome = new RecordIncome(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::EUR),
    );

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
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $nonAttributableAllowableCostRecorded = new NonAttributableAllowableCostRecorded(
        taxYear: $this->taxYear,
        date: $recordNonAttributableAllowableCost->date,
        amount: $recordNonAttributableAllowableCost->amount,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($recordNonAttributableAllowableCost)
        ->then($nonAttributableAllowableCostRecorded);
});

it('cannot record a non-attributable allowable cost because the currency is different', function () {
    $nonAttributableAllowableCostRecorded = new NonAttributableAllowableCostRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    $recordNonAttributableAllowableCost = new RecordNonAttributableAllowableCost(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
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

<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Events\CapitalGainRecorded;
use Domain\Aggregates\TaxYear\Events\CapitalGainReverted;
use Domain\Aggregates\TaxYear\Events\CapitalLossRecorded;
use Domain\Aggregates\TaxYear\Events\CapitalLossReverted;
use Domain\Aggregates\TaxYear\Events\IncomeRecorded;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostRecorded;
use Domain\Enums\FiatCurrency;
use Domain\Tests\Aggregates\TaxYear\Projectors\TaxYearSummaryProjectorTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(TaxYearSummaryProjectorTestCase::class);

it('can handle a capital gain', function () {
    $capitalGainRecorded = new CapitalGainRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
        costBasis: new FiatAmount('50', FiatCurrency::GBP),
        proceeds: new FiatAmount('150', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalGainRecorded))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('recordCapitalGain')
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount, FiatAmount $costBasis, FiatAmount $proceeds) use ($capitalGainRecorded) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $capitalGainRecorded->amount
                    && $costBasis === $capitalGainRecorded->costBasis
                    && $proceeds === $capitalGainRecorded->proceeds;
            })
            ->once());
});

it('can handle a capital gain reversion', function () {
    $capitalGainReverted = new CapitalGainReverted(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
        costBasis: new FiatAmount('50', FiatCurrency::GBP),
        proceeds: new FiatAmount('150', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalGainReverted))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('revertCapitalGain')
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount, FiatAmount $costBasis, FiatAmount $proceeds) use ($capitalGainReverted) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $capitalGainReverted->amount
                    && $costBasis === $capitalGainReverted->costBasis
                    && $proceeds === $capitalGainReverted->proceeds;
            })
            ->once());
});

it('can handle a capital loss', function () {
    $capitalLossRecorded = new CapitalLossRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
        costBasis: new FiatAmount('50', FiatCurrency::GBP),
        proceeds: new FiatAmount('150', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalLossRecorded))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('recordCapitalLoss')
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount, FiatAmount $costBasis, FiatAmount $proceeds) use ($capitalLossRecorded) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $capitalLossRecorded->amount
                    && $costBasis === $capitalLossRecorded->costBasis
                    && $proceeds === $capitalLossRecorded->proceeds;
            })
            ->once());
});

it('can handle a capital loss reversion', function () {
    $capitalLossReverted = new CapitalLossReverted(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
        costBasis: new FiatAmount('50', FiatCurrency::GBP),
        proceeds: new FiatAmount('150', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalLossReverted))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('revertCapitalLoss')
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount, FiatAmount $costBasis, FiatAmount $proceeds) use ($capitalLossReverted) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $capitalLossReverted->amount
                    && $costBasis === $capitalLossReverted->costBasis
                    && $proceeds === $capitalLossReverted->proceeds;
            })
            ->once());
});

it('can handle some income', function () {
    $incomeRecorded = new IncomeRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($incomeRecorded))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('recordIncome')
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount) use ($incomeRecorded) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $incomeRecorded->amount;
            })
            ->once());
});

it('can handle a non-attributable allowable cost', function () {
    $nonAttributableAllowableCostRecorded = new NonAttributableAllowableCostRecorded(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonAttributableAllowableCostRecorded))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('recordNonAttributableAllowableCost')
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount) use ($nonAttributableAllowableCostRecorded) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $nonAttributableAllowableCostRecorded->amount;
            })
            ->once());
});

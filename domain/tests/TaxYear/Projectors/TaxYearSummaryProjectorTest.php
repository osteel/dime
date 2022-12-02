<?php

use Domain\Enums\FiatCurrency;
use Domain\TaxYear\Events\CapitalGainRecorded;
use Domain\TaxYear\Events\CapitalGainReverted;
use Domain\TaxYear\Events\CapitalLossRecorded;
use Domain\TaxYear\Events\CapitalLossReverted;
use Domain\TaxYear\Events\IncomeRecorded;
use Domain\TaxYear\Events\NonAttributableAllowableCostRecorded;
use Domain\Tests\TaxYear\Projectors\TaxYearSummaryProjectorTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(TaxYearSummaryProjectorTestCase::class);

it('can handle a capital gain', function () {
    $capitalGainRecorded = new CapitalGainRecorded(
        taxYear: $this->taxYear,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalGainRecorded))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('recordCapitalGain')
            ->once()
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount) use ($capitalGainRecorded) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $capitalGainRecorded->amount;
            }));
});

it('can handle a capital gain reversion', function () {
    $capitalGainReverted = new CapitalGainReverted(
        taxYear: $this->taxYear,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalGainReverted))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('revertCapitalGain')
            ->once()
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount) use ($capitalGainReverted) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $capitalGainReverted->amount;
            }));
});

it('can handle a capital loss', function () {
    $capitalLossRecorded = new CapitalLossRecorded(
        taxYear: $this->taxYear,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalLossRecorded))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('recordCapitalLoss')
            ->once()
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount) use ($capitalLossRecorded) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $capitalLossRecorded->amount;
            }));
});

it('can handle a capital loss reversion', function () {
    $capitalLossReverted = new CapitalLossReverted(
        taxYear: $this->taxYear,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalLossReverted))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('revertCapitalLoss')
            ->once()
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount) use ($capitalLossReverted) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $capitalLossReverted->amount;
            }));
});

it('can handle some income', function () {
    $incomeRecorded = new IncomeRecorded(
        taxYear: $this->taxYear,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($incomeRecorded))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('recordIncome')
            ->once()
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount) use ($incomeRecorded) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $incomeRecorded->amount;
            }));
});

it('can handle a non-attributable allowable cost', function () {
    $nonAttributableAllowableCostRecorded = new NonAttributableAllowableCostRecorded(
        taxYear: $this->taxYear,
        amount: new FiatAmount('100', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonAttributableAllowableCostRecorded))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('recordNonAttributableAllowableCost')
            ->once()
            ->withArgs(function (AggregateRootId $taxYearId, string $taxYear, FiatAmount $amount) use ($nonAttributableAllowableCostRecorded) {
                return $taxYearId->toString() === $this->aggregateRootId->toString()
                    && $taxYear === $this->taxYear
                    && $amount === $nonAttributableAllowableCostRecorded->amount;
            }));
});

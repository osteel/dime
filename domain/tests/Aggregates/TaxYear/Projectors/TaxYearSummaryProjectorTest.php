<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdated;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdateReverted;
use Domain\Aggregates\TaxYear\Events\IncomeUpdated;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostUpdated;
use Domain\Aggregates\TaxYear\Projectors\Exceptions\TaxYearSummaryProjectionException;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Tests\Aggregates\TaxYear\Projectors\TaxYearSummaryProjectorTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(TaxYearSummaryProjectorTestCase::class);

it('can handle a capital gain update', function (string $costBasis, string $proceeds, string $capitalGainDifference) {
    $capitalGainUpdated = new CapitalGainUpdated(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(FiatAmount::GBP('100'), FiatAmount::GBP('100')),
        newCapitalGain: new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds)),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalGainUpdated))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('updateCapitalGain')
            ->withArgs(fn (AggregateRootId $taxYearId, CapitalGain $capitalGain) => $taxYearId->toString() === $this->aggregateRootId->toString()
                && $capitalGain->isEqualTo($capitalGainUpdated->newCapitalGain)
                && (string) $capitalGain->difference->quantity === $capitalGainDifference)
            ->once());
})->with([
    'gain' => ['50', '150', '100'],
    'loss' => ['150', '50', '-100'],
]);

it('can handle a capital gain update reversion', function (string $costBasis, string $proceeds, string $capitalGainDifference) {
    $capitalGainUpdateReverted = new CapitalGainUpdateReverted(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(FiatAmount::GBP('200'), FiatAmount::GBP('200')),
        newCapitalGain: new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds)),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalGainUpdateReverted))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('updateCapitalGain')
            ->withArgs(fn (AggregateRootId $taxYearId, CapitalGain $capitalGain) => $taxYearId->toString() === $this->aggregateRootId->toString()
                && $capitalGain->isEqualTo($capitalGainUpdateReverted->newCapitalGain)
                && (string) $capitalGain->difference->quantity === $capitalGainDifference)
            ->once());
})->with([
    'gain' => ['50', '150', '100'],
    'loss' => ['150', '50', '-100'],
]);

it('can handle an income update', function () {
    $incomeUpdated = new IncomeUpdated(
        date: LocalDate::parse('2015-10-21'),
        incomeUpdate: FiatAmount::GBP('100'),
        newIncome: FiatAmount::GBP('150'),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($incomeUpdated))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('updateIncome')
            ->withArgs(fn (AggregateRootId $taxYearId, FiatAmount $income) => $taxYearId->toString() === $this->aggregateRootId->toString()
                && $income === $incomeUpdated->newIncome)
            ->once());
});

it('can handle a non-attributable allowable cost update', function () {
    $nonAttributableAllowableCostUpdated = new NonAttributableAllowableCostUpdated(
        date: LocalDate::parse('2015-10-21'),
        nonAttributableAllowableCostChange: FiatAmount::GBP('100'),
        newNonAttributableAllowableCost: FiatAmount::GBP('150'),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonAttributableAllowableCostUpdated))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('updateNonAttributableAllowableCost')
            ->withArgs(fn (AggregateRootId $taxYearId, FiatAmount $nonAttributableAllowableCost) => $taxYearId->toString() === $this->aggregateRootId->toString()
                && $nonAttributableAllowableCost === $nonAttributableAllowableCostUpdated->newNonAttributableAllowableCost)
            ->once());
});

it('throws an exception when the aggregate root ID is missing', function () {
    $capitalGainUpdated = new CapitalGainUpdated(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(FiatAmount::GBP('100'), FiatAmount::GBP('100')),
        newCapitalGain: new CapitalGain(FiatAmount::GBP('100'), FiatAmount::GBP('100')),
    );

    /** @var MessageConsumerTestCase $this */
    $this->when(new Message($capitalGainUpdated))
        ->expectToFail(TaxYearSummaryProjectionException::missingTaxYearId(CapitalGainUpdated::class));
});

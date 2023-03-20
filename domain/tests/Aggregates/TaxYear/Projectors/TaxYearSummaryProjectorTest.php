<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdated;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdateReverted;
use Domain\Aggregates\TaxYear\Events\IncomeUpdated;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostUpdated;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Tests\Aggregates\TaxYear\Projectors\TaxYearSummaryProjectorTestCase;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(TaxYearSummaryProjectorTestCase::class);

it('can handle a capital gain update', function (string $costBasis, string $proceeds, string $capitalGainDifference) {
    $capitalGainUpdated = new CapitalGainUpdated(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        capitalGain: new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds)),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalGainUpdated))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('updateCapitalGain')
            ->withArgs(fn (AggregateRootId $taxYearId, string $taxYear, CapitalGain $capitalGain) => $taxYearId->toString() === $this->aggregateRootId->toString()
                && $taxYear === $this->taxYear
                && $capitalGain->isEqualTo($capitalGainUpdated->capitalGain)
                && (string) $capitalGain->difference->quantity === $capitalGainDifference)
            ->once());
})->with([
    'gain' => ['50', '150', '100'],
    'loss' => ['150', '50', '-100'],
]);

it('can handle a capital gain update reversion', function (string $costBasis, string $proceeds, string $capitalGainDifference) {
    $capitalGainUpdateReverted = new CapitalGainUpdateReverted(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        capitalGain: new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds)),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($capitalGainUpdateReverted))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('updateCapitalGain')
            ->withArgs(fn (AggregateRootId $taxYearId, string $taxYear, CapitalGain $capitalGain) => $taxYearId->toString() === $this->aggregateRootId->toString()
                && $taxYear === $this->taxYear
                && $capitalGain->isEqualTo($capitalGainUpdateReverted->capitalGain->opposite())
                && (string) $capitalGain->difference->quantity === (string) (new Quantity($capitalGainDifference))->opposite())
            ->once());
})->with([
    'gain' => ['50', '150', '100'],
    'loss' => ['150', '50', '-100'],
]);

it('can handle an income update', function () {
    $incomeUpdated = new IncomeUpdated(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        income: FiatAmount::GBP('100'),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($incomeUpdated))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('updateIncome')
            ->withArgs(fn (AggregateRootId $taxYearId, string $taxYear, FiatAmount $income) => $taxYearId->toString() === $this->aggregateRootId->toString()
                && $taxYear === $this->taxYear
                && $income === $incomeUpdated->income)
            ->once());
});

it('can handle a non-attributable allowable cost update', function () {
    $nonAttributableAllowableCostUpdated = new NonAttributableAllowableCostUpdated(
        taxYear: $this->taxYear,
        date: LocalDate::parse('2015-10-21'),
        nonAttributableAllowableCost: FiatAmount::GBP('100'),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonAttributableAllowableCostUpdated))
        ->then(fn () => $this->taxYearSummaryRepository->shouldHaveReceived('updateNonAttributableAllowableCost')
            ->withArgs(fn (AggregateRootId $taxYearId, string $taxYear, FiatAmount $nonAttributableAllowableCost) => $taxYearId->toString() === $this->aggregateRootId->toString()
                && $taxYear === $this->taxYear
                && $nonAttributableAllowableCost === $nonAttributableAllowableCostUpdated->nonAttributableAllowableCost)
            ->once());
});

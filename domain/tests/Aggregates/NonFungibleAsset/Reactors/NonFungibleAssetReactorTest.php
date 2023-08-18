<?php

use Brick\DateTime\LocalDate;
use Domain\Actions\UpdateSummary;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetAcquired;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetCostBasisIncreased;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetDisposedOf;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Tests\Aggregates\NonFungibleAsset\Reactors\NonFungibleAssetReactorTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(NonFungibleAssetReactorTestCase::class);

it('can handle a capital gain', function () {
    $nonFungibleAssetDisposedOf = new NonFungibleAssetDisposedOf(
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
        proceeds: FiatAmount::GBP('101'),
        forFiat: false,
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonFungibleAssetDisposedOf))
        ->then(function () {
            return $this->runner->shouldHaveReceived('run', fn (UpdateCapitalGain $action) => $action->capitalGainUpdate->difference->isEqualTo('1'))->once()
                && $this->runner->shouldNotHaveReceived('run', fn ($action) => $action instanceof UpdateSummary);
        });
});

it('can handle a capital loss', function () {
    $nonFungibleAssetDisposedOf = new NonFungibleAssetDisposedOf(
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
        proceeds: FiatAmount::GBP('99'),
        forFiat: false,
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonFungibleAssetDisposedOf))
        ->then(function () {
            return $this->runner->shouldHaveReceived('run', fn (UpdateCapitalGain $action) => $action->capitalGainUpdate->difference->isEqualTo('-1'))->once()
                && $this->runner->shouldNotHaveReceived('run', fn ($action) => $action instanceof UpdateSummary);
        });
});

it('can handle a summary update for an acquisition', function () {
    $nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('10'),
        forFiat: true,
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonFungibleAssetAcquired))
        ->then(fn () => $this->runner->shouldHaveReceived(
            'run',
            fn ($action) => $action instanceof UpdateSummary && $action->fiatBalanceUpdate->isEqualTo(FiatAmount::GBP('-10')),
        )->once());
});

it('can handle a summary update for a cost basis increase', function () {
    $nonFungibleAssetCostBasisIncreased = new NonFungibleAssetCostBasisIncreased(
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: FiatAmount::GBP('10'),
        newCostBasis: FiatAmount::GBP('20'),
        forFiat: true,
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonFungibleAssetCostBasisIncreased))
        ->then(fn () => $this->runner->shouldHaveReceived(
            'run',
            fn ($action) => $action instanceof UpdateSummary && $action->fiatBalanceUpdate->isEqualTo(FiatAmount::GBP('-10')),
        )->once());
});

it('can handle a summary update for a disposal', function () {
    $nonFungibleAssetDisposedOf = new NonFungibleAssetDisposedOf(
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
        proceeds: $proceeds = FiatAmount::GBP('101'),
        forFiat: true,
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonFungibleAssetDisposedOf))
        ->then(function () use ($proceeds) {
            return $this->runner->shouldHaveReceived('run', fn ($action) => $action instanceof UpdateCapitalGain)->once()
                && $this->runner->shouldHaveReceived('run', fn ($action) => $action instanceof UpdateSummary && $action->fiatBalanceUpdate->isEqualTo($proceeds))->once();
        });
});

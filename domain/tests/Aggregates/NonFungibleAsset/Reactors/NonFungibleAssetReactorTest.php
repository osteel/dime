<?php

use Brick\DateTime\LocalDate;
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
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonFungibleAssetDisposedOf))
        ->then(fn () => $this->runner->shouldHaveReceived(
            'run',
            fn (UpdateCapitalGain $action) => $action->capitalGain->difference->isEqualTo('1'),
        )->once());
});

it('can handle a capital loss', function () {
    $nonFungibleAssetDisposedOf = new NonFungibleAssetDisposedOf(
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
        proceeds: FiatAmount::GBP('99'),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nonFungibleAssetDisposedOf))
        ->then(fn () => $this->runner->shouldHaveReceived(
            'run',
            fn (UpdateCapitalGain $action) => $action->capitalGain->difference->isEqualTo('-1'),
        )->once());
});

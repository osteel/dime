<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Tests\Aggregates\SharePoolingAsset\Reactors\SharePoolingAssetReactorTestCase;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(SharePoolingAssetReactorTestCase::class);

it('can handle a capital gain update', function (string $costBasis, string $proceeds, string $capitalGain) {
    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP($costBasis),
            proceeds: FiatAmount::GBP($proceeds),
        ),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetDisposedOf))
        ->then(fn () => $this->dispatcher->shouldHaveReceived(
            'dispatchSync',
            fn (UpdateCapitalGain $action) => $action->capitalGain->difference->isEqualTo($capitalGain)
        )->once());
})->with([
    'gain' => ['100', '101', '1'],
    'loss' => ['100', '99', '-1'],
]);

it('can handle a capital gain update reversion', function (string $costBasis, string $proceeds, string $capitalGain) {
    $sharePoolingAssetDisposalReverted = new SharePoolingAssetDisposalReverted(
        new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP($costBasis),
            proceeds: FiatAmount::GBP($proceeds),
        ),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetDisposalReverted))
        ->then(fn () => $this->dispatcher->shouldHaveReceived(
            'dispatchSync',
            fn (RevertCapitalGainUpdate $action) => $action->capitalGain->difference->isEqualTo($capitalGain)
        )->once());
})->with([
    'gain' => ['100', '101', '1'],
    'loss' => ['100', '99', '-1'],
]);

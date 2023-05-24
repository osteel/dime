<?php

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
        SharePoolingAssetDisposal::factory()->make([
            'quantity' => new Quantity('100'),
            'costBasis' => FiatAmount::GBP($costBasis),
            'proceeds' => FiatAmount::GBP($proceeds),
        ]),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetDisposedOf))
        ->then(fn () => $this->runner->shouldHaveReceived(
            'run',
            fn (UpdateCapitalGain $action) => $action->capitalGainUpdate->difference->isEqualTo($capitalGain)
        )->once());
})->with([
    'gain' => ['100', '101', '1'],
    'loss' => ['100', '99', '-1'],
]);

it('can handle a capital gain update reversion', function (string $costBasis, string $proceeds, string $capitalGain) {
    $sharePoolingAssetDisposalReverted = new SharePoolingAssetDisposalReverted(
        SharePoolingAssetDisposal::factory()->make([
            'quantity' => new Quantity('100'),
            'costBasis' => FiatAmount::GBP($costBasis),
            'proceeds' => FiatAmount::GBP($proceeds),
        ]),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetDisposalReverted))
        ->then(fn () => $this->runner->shouldHaveReceived(
            'run',
            fn (RevertCapitalGainUpdate $action) => $action->capitalGain->difference->isEqualTo($capitalGain)
        )->once());
})->with([
    'gain' => ['100', '101', '1'],
    'loss' => ['100', '99', '-1'],
]);

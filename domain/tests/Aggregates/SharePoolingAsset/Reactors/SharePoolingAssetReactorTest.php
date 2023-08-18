<?php

use Domain\Actions\UpdateSummary;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetAcquired;
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
            'forFiat' => false,
        ]),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetDisposedOf))
        ->then(function () use ($capitalGain) {
            return $this->runner->shouldHaveReceived('run', fn (UpdateCapitalGain $action) => $action->capitalGainUpdate->difference->isEqualTo($capitalGain))->once()
                && $this->runner->shouldNotHaveReceived('run', fn ($action) => $action instanceof UpdateSummary);
        });
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
            'forFiat' => false,
        ]),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetDisposalReverted))
        ->then(function () use ($capitalGain) {
            return $this->runner->shouldHaveReceived('run', fn (RevertCapitalGainUpdate $action) => $action->capitalGainUpdate->difference->isEqualTo($capitalGain))->once()
                && $this->runner->shouldNotHaveReceived('run', fn ($action) => $action instanceof UpdateSummary);
        });
})->with([
    'gain' => ['100', '101', '1'],
    'loss' => ['100', '99', '-1'],
]);

it('can handle a summary update for an acquisition', function () {
    $sharePoolingAssetAcquired = new SharePoolingAssetAcquired(
        SharePoolingAssetAcquisition::factory()->make([
            'costBasis' => FiatAmount::GBP('10'),
            'forFiat' => true,
        ]),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetAcquired))
        ->then(fn () => $this->runner->shouldHaveReceived(
            'run',
            fn ($action) => $action instanceof UpdateSummary && $action->fiatBalanceUpdate->isEqualTo(FiatAmount::GBP('-10')),
        )->once());
});

it('can handle a summary update for a disposal', function () {
    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        SharePoolingAssetDisposal::factory()->make([
            'proceeds' => $proceeds = FiatAmount::GBP('10'),
            'forFiat' => true,
        ]),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetDisposedOf))
        ->then(function () use ($proceeds) {
            return $this->runner->shouldHaveReceived('run', fn ($action) => $action instanceof UpdateCapitalGain)->once()
                && $this->runner->shouldHaveReceived('run', fn ($action) => $action instanceof UpdateSummary && $action->fiatBalanceUpdate->isEqualTo($proceeds))->once();
        });
});

it('can handle a summary update for a disposal reversion', function () {
    $sharePoolingAssetDisposalReverted = new SharePoolingAssetDisposalReverted(
        SharePoolingAssetDisposal::factory()->make([
            'proceeds' => FiatAmount::GBP('10'),
            'forFiat' => true,
        ]),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetDisposalReverted))
        ->then(function () {
            return $this->runner->shouldHaveReceived('run', fn ($action) => $action instanceof RevertCapitalGainUpdate)->once()
                && $this->runner->shouldHaveReceived('run', fn ($action) => $action instanceof UpdateSummary && $action->fiatBalanceUpdate->isEqualTo(FiatAmount::GBP('-10')))->once();
        });
});

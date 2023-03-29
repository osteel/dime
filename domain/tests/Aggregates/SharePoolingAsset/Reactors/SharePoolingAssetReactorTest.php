<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetDisposal;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Tests\Aggregates\SharePoolingAsset\Reactors\SharePoolingAssetReactorTestCase;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(SharePoolingAssetReactorTestCase::class);

it('can handle a capital gain update', function (string $costBasis, string $proceeds, string $capitalGain) {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYearSpy);

    $sharePoolingAssetDisposedOf = new SharePoolingAssetDisposedOf(
        new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP($costBasis),
            proceeds: FiatAmount::GBP($proceeds),
            sameDayQuantityAllocation: new QuantityAllocation(),
            thirtyDayQuantityAllocation: new QuantityAllocation(),
        ),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetDisposedOf))
        ->then(fn () => $taxYearSpy->shouldHaveReceived(
            'updateCapitalGain',
            fn (UpdateCapitalGain $action) => $action->capitalGain->difference->isEqualTo($capitalGain)
        )->once());
})->with([
    'gain' => ['100', '101', '1'],
    'loss' => ['100', '99', '-1'],
]);

it('can handle a capital gain update reversion', function (string $costBasis, string $proceeds, string $capitalGain) {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYearSpy);

    $sharePoolingAssetDisposalReverted = new SharePoolingAssetDisposalReverted(
        new SharePoolingAssetDisposal(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP($costBasis),
            proceeds: FiatAmount::GBP($proceeds),
            sameDayQuantityAllocation: new QuantityAllocation(),
            thirtyDayQuantityAllocation: new QuantityAllocation(),
        ),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingAssetDisposalReverted))
        ->then(fn () => $taxYearSpy->shouldHaveReceived(
            'revertCapitalGainUpdate',
            fn (RevertCapitalGainUpdate $action) => $action->capitalGain->difference->isEqualTo($capitalGain)
        )->once());
})->with([
    'gain' => ['100', '101', '1'],
    'loss' => ['100', '99', '-1'],
]);

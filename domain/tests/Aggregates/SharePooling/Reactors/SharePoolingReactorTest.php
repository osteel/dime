<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\Aggregates\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Tests\Aggregates\SharePooling\Reactors\SharePoolingReactorTestCase;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(SharePoolingReactorTestCase::class);

it('can handle a capital gain update', function (string $costBasis, string $proceeds, string $capitalGain) {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYearSpy);

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP($costBasis),
            proceeds: FiatAmount::GBP($proceeds),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingTokenDisposedOf))
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

    $sharePoolingTokenDisposalReverted = new SharePoolingTokenDisposalReverted(
        new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: FiatAmount::GBP($costBasis),
            proceeds: FiatAmount::GBP($proceeds),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingTokenDisposalReverted))
        ->then(fn () => $taxYearSpy->shouldHaveReceived(
            'revertCapitalGainUpdate',
            fn (RevertCapitalGainUpdate $action) => $action->capitalGain->difference->isEqualTo($capitalGain)
        )->once());
})->with([
    'gain' => ['100', '101', '1'],
    'loss' => ['100', '99', '-1'],
]);

<?php

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\Aggregates\SharePooling\ValueObjects\QuantityBreakdown;
use Domain\Aggregates\SharePooling\ValueObjects\SharePoolingTokenDisposal;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalLoss;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalLoss;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Tests\Aggregates\SharePooling\Reactors\SharePoolingReactorTestCase;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(SharePoolingReactorTestCase::class);

it('can handle a capital gain', function () {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYearSpy);

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
            proceeds: new FiatAmount('101', FiatCurrency::GBP),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingTokenDisposedOf))
        ->then(fn () => $taxYearSpy->shouldHaveReceived(
            'recordCapitalGain',
            fn (RecordCapitalGain $action) => $action->amount->isEqualTo('1')
        )->once());
});

it('can handle a capital loss', function () {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYearSpy);

    $sharePoolingTokenDisposedOf = new SharePoolingTokenDisposedOf(
        new SharePoolingTokenDisposal(
            quantity: new Quantity('100'),
            date: LocalDate::parse('2015-10-21'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
            proceeds: new FiatAmount('99', FiatCurrency::GBP),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingTokenDisposedOf))
        ->then(fn () => $taxYearSpy->shouldHaveReceived(
            'recordCapitalLoss',
            fn (RecordCapitalLoss $action) => $action->amount->isEqualTo('1')
        )->once());
});

it('can handle a capital gain reversion', function () {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYearSpy);

    $sharePoolingTokenDisposalReverted = new SharePoolingTokenDisposalReverted(
        new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
            proceeds: new FiatAmount('101', FiatCurrency::GBP),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingTokenDisposalReverted))
        ->then(fn () => $taxYearSpy->shouldHaveReceived(
            'revertCapitalGain',
            fn (RevertCapitalGain $action) => $action->amount->isEqualTo('1')
        )->once());
});

it('can handle a capital loss reversion', function () {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYearSpy);

    $sharePoolingTokenDisposalReverted = new SharePoolingTokenDisposalReverted(
        new SharePoolingTokenDisposal(
            date: LocalDate::parse('2015-10-21'),
            quantity: new Quantity('100'),
            costBasis: new FiatAmount('100', FiatCurrency::GBP),
            proceeds: new FiatAmount('99', FiatCurrency::GBP),
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
        ),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($sharePoolingTokenDisposalReverted))
        ->then(fn () => $taxYearSpy->shouldHaveReceived(
            'revertCapitalLoss',
            fn (RevertCapitalLoss $action) => $action->amount->isEqualTo('1')
        )->once());
});

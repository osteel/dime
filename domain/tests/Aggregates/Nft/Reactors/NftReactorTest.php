<?php

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\Aggregates\Nft\Events\NftDisposedOf;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalGain;
use Domain\Aggregates\TaxYear\Actions\RecordCapitalLoss;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Tests\Aggregates\Nft\Reactors\NftReactorTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(NftReactorTestCase::class);

it('can handle a capital gain', function () {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYearSpy);

    $nftDisposedOf = new NftDisposedOf(
        date: LocalDate::parse('2015-10-21'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
        proceeds: new FiatAmount('101', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nftDisposedOf))
        ->then(function () use ($taxYearSpy) {
            return $taxYearSpy->shouldHaveReceived(
                'recordCapitalGain',
                fn (RecordCapitalGain $action) => $action->amount->isEqualTo('1'),
            )->once();
        });
});

it('can handle a capital loss', function () {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYearSpy);

    $nftDisposedOf = new NftDisposedOf(
        date: LocalDate::parse('2015-10-21'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
        proceeds: new FiatAmount('99', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nftDisposedOf))
        ->then(function () use ($taxYearSpy) {
            return $taxYearSpy->shouldHaveReceived(
                'recordCapitalLoss',
                fn (RecordCapitalLoss $action) => $action->amount->isEqualTo('1'),
            )->once();
        });
});

<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\Nft\Events\NftDisposedOf;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
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
        costBasis: FiatAmount::GBP('100'),
        proceeds: FiatAmount::GBP('101'),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nftDisposedOf))
        ->then(fn () => $taxYearSpy->shouldHaveReceived(
            'updateCapitalGain',
            fn (UpdateCapitalGain $action) => $action->capitalGain->difference->isEqualTo('1'),
        )->once());
});

it('can handle a capital loss', function () {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYearSpy);

    $nftDisposedOf = new NftDisposedOf(
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
        proceeds: FiatAmount::GBP('99'),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->aggregateRootId)
        ->when(new Message($nftDisposedOf))
        ->then(fn () => $taxYearSpy->shouldHaveReceived(
            'updateCapitalGain',
            fn (UpdateCapitalGain $action) => $action->capitalGain->difference->isEqualTo('-1'),
        )->once());
});

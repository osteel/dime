<?php

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\Nft\Events\NftDisposedOf;
use Domain\Nft\NftId;
use Domain\TaxYear\Actions\RecordCapitalGain;
use Domain\TaxYear\Actions\RecordCapitalLoss;
use Domain\TaxYear\TaxYear;
use Domain\TaxYear\TaxYearId;
use Domain\Tests\Nft\Reactors\NftReactorTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\TestUtilities\MessageConsumerTestCase;

uses(NftReactorTestCase::class);

beforeEach(function () {
    $this->nftId = NftId::generate();
});

it('can handle a capital gain', function () {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);

    $taxYearId = TaxYearId::fromYear(2015);
    $this->taxYearRepository->shouldReceive('save')->once()->withArgs(fn (TaxYearId $id) => $id->id === $taxYearId->id);

    $nftDisposedOf = new NftDisposedOf(
        nftId: $this->nftId,
        date: LocalDate::parse('2015-10-21'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
        proceeds: new FiatAmount('101', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->nftId)
        ->when(new Message($nftDisposedOf))
        ->then(fn () => $taxYearSpy->shouldHaveReceived('recordCapitalGain')
            ->once()
            ->withArgs(fn (RecordCapitalGain $action) => $action->amount->isEqualTo('1')));
});

it('can handle a capital loss', function () {
    $taxYearSpy = Mockery::spy(TaxYear::class);
    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYearSpy);

    $taxYearId = TaxYearId::fromYear(2015);
    $this->taxYearRepository->shouldReceive('save')->once()->withArgs(fn (TaxYearId $id) => $id->id === $taxYearId->id);

    $nftDisposedOf = new NftDisposedOf(
        nftId: $this->nftId,
        date: LocalDate::parse('2015-10-21'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
        proceeds: new FiatAmount('99', FiatCurrency::GBP),
    );

    /** @var MessageConsumerTestCase $this */
    $this->givenNextMessagesHaveAggregateRootIdOf($this->nftId)
        ->when(new Message($nftDisposedOf))
        ->then(fn () => $taxYearSpy->shouldHaveReceived('recordCapitalLoss')
            ->once()
            ->withArgs(fn (RecordCapitalLoss $action) => $action->amount->isEqualTo('1')));
});

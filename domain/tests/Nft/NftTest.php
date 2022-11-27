<?php

use Brick\DateTime\LocalDate;
use Domain\Enums\FiatCurrency;
use Domain\Nft\Actions\AcquireNft;
use Domain\Nft\Actions\DisposeOfNft;
use Domain\Nft\Actions\IncreaseNftCostBasis;
use Domain\Nft\Events\NftAcquired;
use Domain\Nft\Events\NftCostBasisIncreased;
use Domain\Nft\Events\NftDisposedOf;
use Domain\Nft\Exceptions\NftException;
use Domain\Tests\Nft\NftTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(NftTestCase::class);

it('can acquire a NFT', function () {
    $acquireNft = new AcquireNft(
        date: LocalDate::parse('2015-10-21'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $nftAcquired = new NftAcquired(
        date: $acquireNft->date,
        costBasis: $acquireNft->costBasis,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($acquireNft)
        ->then($nftAcquired);
});

it('cannot acquire the same NFT more than once', function () {
    $nftAcquired = new NftAcquired(
        date: LocalDate::parse('2015-10-21'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $acquireSameNft = new AcquireNft(
        date: LocalDate::parse('2015-10-21'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $alreadyAcquired = NftException::alreadyAcquired($this->aggregateRootId);

    /** @var AggregateRootTestCase $this */
    $this->given($nftAcquired)
        ->when($acquireSameNft)
        ->expectToFail($alreadyAcquired);
});

it('can increase the cost basis of a NFT', function () {
    $nftAcquired = new NftAcquired(
        date: LocalDate::parse('2015-10-21'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $increaseNftCostBasis = new IncreaseNftCostBasis(
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: new FiatAmount('50', FiatCurrency::GBP),
    );

    $nftCostBasisIncreased = new NftCostBasisIncreased(
        date: $increaseNftCostBasis->date,
        costBasisIncrease: $increaseNftCostBasis->costBasisIncrease,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nftAcquired)
        ->when($increaseNftCostBasis)
        ->then($nftCostBasisIncreased);
});

it('cannot increase the cost basis of a NFT that has not been acquired', function () {
    $increaseNftCostBasis = new IncreaseNftCostBasis(
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: new FiatAmount('100', FiatCurrency::GBP),
    );

    $cannotIncreaseCostBasis = NftException::cannotIncreaseCostBasisBeforeAcquisition($this->aggregateRootId);

    /** @var AggregateRootTestCase $this */
    $this->when($increaseNftCostBasis)
        ->expectToFail($cannotIncreaseCostBasis);
});

it('cannot increase the cost basis of a NFT because the currency is different', function () {
    $nftAcquired = new NftAcquired(
        date: LocalDate::parse('2015-10-21'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $increaseNftCostBasis = new IncreaseNftCostBasis(
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotIncreaseCostBasis = NftException::cannotIncreaseCostBasisFromDifferentCurrency(
        nftId: $this->aggregateRootId,
        from: FiatCurrency::GBP,
        to: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nftAcquired)
        ->when($increaseNftCostBasis)
        ->expectToFail($cannotIncreaseCostBasis);
});

it('can dispose of a NFT', function () {
    $nftAcquired = new NftAcquired(
        date: LocalDate::parse('2015-10-21'),
        costBasis: new FiatAmount('100', FiatCurrency::GBP),
    );

    $disposeOfNft = new DisposeOfNft(
        date: LocalDate::parse('2015-10-21'),
        proceeds: new FiatAmount('150', FiatCurrency::GBP),
    );

    $nftDisposedOf = new NftDisposedOf(
        date: $disposeOfNft->date,
        costBasis: $nftAcquired->costBasis,
        proceeds: $disposeOfNft->proceeds,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nftAcquired)
        ->when($disposeOfNft)
        ->then($nftDisposedOf);
});

it('cannot dispose of a NFT that has not been acquired', function () {
    $disposeOfNft = new DisposeOfNft(
        date: LocalDate::parse('2015-10-21'),
        proceeds: new FiatAmount('100', FiatCurrency::GBP),
    );

    $cannotDisposeOf = NftException::cannotDisposeOfBeforeAcquisition($this->aggregateRootId);

    /** @var AggregateRootTestCase $this */
    $this->when($disposeOfNft)
        ->expectToFail($cannotDisposeOf);
});

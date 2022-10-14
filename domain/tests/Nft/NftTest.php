<?php

use Domain\Enums\Currency;
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

beforeEach(function () {
    $this->nftId = $this->aggregateRootId();
});

it('can acquire a NFT', function () {
    $acquireNft = new AcquireNft(nftId: $this->nftId, costBasis: new FiatAmount('100', Currency::GBP));
    $nftAcquired = new NftAcquired(nftId: $acquireNft->nftId, costBasis: $acquireNft->costBasis);

    /** @var AggregateRootTestCase $this */
    $this->when($acquireNft)
        ->then($nftAcquired);
});

it('cannot acquire the same NFT more than once', function () {
    $nftAcquired = new NftAcquired(nftId: $this->nftId, costBasis: new FiatAmount('100', Currency::GBP));
    $acquireSameNft = new AcquireNft(nftId: $this->nftId, costBasis: new FiatAmount('100', Currency::GBP));
    $alreadyAcquired = NftException::alreadyAcquired($acquireSameNft->nftId);

    /** @var AggregateRootTestCase $this */
    $this->given($nftAcquired)
        ->when($acquireSameNft)
        ->expectToFail($alreadyAcquired);
});

it('can increase the cost basis of a NFT', function () {
    $nftAcquired = new NftAcquired(nftId: $this->nftId, costBasis: new FiatAmount('100', Currency::GBP));
    $increaseNftCostBasis = new IncreaseNftCostBasis(nftId: $this->nftId, costBasisIncrease: new FiatAmount('50', Currency::GBP));
    $nftCostBasisIncreased = new NftCostBasisIncreased(
        nftId: $increaseNftCostBasis->nftId,
        costBasisIncrease: $increaseNftCostBasis->costBasisIncrease,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nftAcquired)
        ->when($increaseNftCostBasis)
        ->then($nftCostBasisIncreased);
});

it('cannot increase the cost basis of a NFT that has not been acquired', function () {
    $increaseNftCostBasis = new IncreaseNftCostBasis(nftId: $this->nftId, costBasisIncrease: new FiatAmount('100', Currency::GBP));
    $cannotIncreaseCostBasis = NftException::cannotIncreaseCostBasisBeforeAcquisition($increaseNftCostBasis->nftId);

    /** @var AggregateRootTestCase $this */
    $this->when($increaseNftCostBasis)
        ->expectToFail($cannotIncreaseCostBasis);
});

it('cannot increase the cost basis of a NFT because the currency is different', function () {
    $nftAcquired = new NftAcquired(nftId: $this->nftId, costBasis: new FiatAmount('100', Currency::GBP));
    $increaseNftCostBasis = new IncreaseNftCostBasis(nftId: $this->nftId, costBasisIncrease: new FiatAmount('100', Currency::EUR));
    $cannotIncreaseCostBasis = NftException::cannotIncreaseCostBasisFromDifferentCurrency(
        nftId: $increaseNftCostBasis->nftId,
        from: Currency::GBP,
        to: Currency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nftAcquired)
        ->when($increaseNftCostBasis)
        ->expectToFail($cannotIncreaseCostBasis);
});

it('can dispose of a NFT', function () {
    $nftAcquired = new NftAcquired(nftId: $this->nftId, costBasis: new FiatAmount('100', Currency::GBP));
    $disposeOfNft = new DisposeOfNft(nftId: $this->nftId, disposalProceeds: new FiatAmount('150', Currency::GBP));
    $nftDisposedOf = new NftDisposedOf(
        nftId: $disposeOfNft->nftId,
        costBasis: $nftAcquired->costBasis,
        disposalProceeds: $disposeOfNft->disposalProceeds,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nftAcquired)
        ->when($disposeOfNft)
        ->then($nftDisposedOf);
});

it('cannot dispose of a NFT that has not been acquired', function () {
    $disposeOfNft = new DisposeOfNft(nftId: $this->nftId, disposalProceeds: new FiatAmount('100', Currency::GBP));
    $cannotDisposeOf = NftException::cannotDisposeOfBeforeAcquisition($disposeOfNft->nftId);

    /** @var AggregateRootTestCase $this */
    $this->when($disposeOfNft)
        ->expectToFail($cannotDisposeOf);
});

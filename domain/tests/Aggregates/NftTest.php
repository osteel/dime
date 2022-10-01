<?php

use Domain\Actions\AcquireNft;
use Domain\Actions\IncreaseNftCostBasis;
use Domain\Aggregates\Exceptions\NftException;
use Domain\Enums\Currency;
use Domain\Events\NftAcquired;
use Domain\Events\NftCostBasisIncreased;
use Domain\Tests\Aggregates\NftTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(NftTestCase::class);

it('can acquire an NFT', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $acquireNft = new AcquireNft(nftId: $nftId, costBasis: new FiatAmount('100', Currency::GBP));
    $nftAcquired = new NftAcquired(nftId: $acquireNft->nftId, costBasis: $acquireNft->costBasis);

    $this->when($acquireNft)
        ->then($nftAcquired);
});

it('cannot acquire the same NFT more than once', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $nftAcquired = new NftAcquired(nftId: $nftId, costBasis: new FiatAmount('100', Currency::GBP));
    $acquireSameNft = new AcquireNft(nftId: $nftId, costBasis: new FiatAmount('100', Currency::GBP));
    $alreadyAcquired = NftException::alreadyAcquired($acquireSameNft->nftId);

    $this->given($nftAcquired)
        ->when($acquireSameNft)
        ->expectToFail($alreadyAcquired);
});

it('can increase the cost basis of an NFT', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $nftAcquired = new NftAcquired(nftId: $nftId, costBasis: new FiatAmount('100', Currency::GBP));
    $increaseNftCostBasis = new IncreaseNftCostBasis(nftId: $nftId, extraCostBasis: new FiatAmount('50', Currency::GBP));
    $nftCostBasisIncreased = new NftCostBasisIncreased(
        nftId: $nftId,
        previousCostBasis: $nftAcquired->costBasis,
        extraCostBasis: $increaseNftCostBasis->extraCostBasis,
        newCostBasis: new FiatAmount('150', Currency::GBP),
    );

    $this->given($nftAcquired)
        ->when($increaseNftCostBasis)
        ->then($nftCostBasisIncreased);
});

it('cannot increase the cost basis of an NFT that has not been acquired yet', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $increaseNftCostBasis = new IncreaseNftCostBasis($nftId, new FiatAmount('100', Currency::GBP));
    $cannotincreaseCostBasis = NftException::cannotincreaseCostBasisBeforeAcquisition($nftId);

    $this->when($increaseNftCostBasis)
        ->expectToFail($cannotincreaseCostBasis);
});

<?php

use Domain\Actions\AcquireNft;
use Domain\Actions\AverageNftCostBasis;
use Domain\Aggregates\Exceptions\NftException;
use Domain\Enums\Currency;
use Domain\Events\NftAcquired;
use Domain\Events\NftCostBasisAveraged;
use Domain\Tests\Aggregates\NftTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(NftTestCase::class);

it('can acquire an NFT', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $acquireNft = new AcquireNft($nftId, new FiatAmount('100', Currency::GBP));
    $nftAcquired = new NftAcquired($acquireNft->nftId, $acquireNft->costBasis);

    $this->when($acquireNft)
        ->then($nftAcquired);
});

it('cannot acquire the same NFT more than once', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $nftAcquired = new NftAcquired($nftId, new FiatAmount('100', Currency::GBP));
    $acquireSameNft = new AcquireNft($nftId, new FiatAmount('100', Currency::GBP));
    $alreadyAcquired = NftException::alreadyAcquired($acquireSameNft->nftId);

    $this->given($nftAcquired)
        ->when($acquireSameNft)
        ->expectToFail($alreadyAcquired);
});

it('can average the cost basis of an NFT', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $nftAcquired = new NftAcquired($nftId, new FiatAmount('100', Currency::GBP));
    $averageNftCostBasis = new AverageNftCostBasis($nftId, new FiatAmount('50', Currency::GBP));
    $nftCostBasisAveraged = new NftCostBasisAveraged($nftId, $averageNftCostBasis->averagingCostBasis);

    $this->given($nftAcquired)
        ->when($averageNftCostBasis)
        ->then($nftCostBasisAveraged);
});

it('cannot average the cost basis of an NFT that has not been acquired yet', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $averageNftCostBasis = new AverageNftCostBasis($nftId, new FiatAmount('100', Currency::GBP));
    $cannotAverageCostBasis = NftException::cannotAverageCostBasisBeforeAcquisition($nftId);

    $this->when($averageNftCostBasis)
        ->expectToFail($cannotAverageCostBasis);
});

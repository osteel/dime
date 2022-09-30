<?php

use Domain\Actions\AcquireNft;
use Domain\Aggregates\Exceptions\NftException;
use Domain\Enums\Currency;
use Domain\Events\NftAcquired;
use Domain\Tests\Aggregates\NftTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(NftTestCase::class);

it('can acquire an NFT', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $action = new AcquireNft($nftId, new FiatAmount('100', Currency::GBP));
    $event = new NftAcquired($action->nftId, $action->costBasis);

    $this->when($action)
        ->then($event);
});

it('cannot acquire the same NFT more than once', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $event = new NftAcquired($nftId, new FiatAmount('100', Currency::GBP));
    $action = new AcquireNft($nftId, new FiatAmount('100', Currency::GBP));
    $exception = NftException::alreadyAcquired($action->nftId);

    $this->given($event)
        ->when($action)
        ->expectToFail($exception);
});

/*
it('can average the cost basis of an NFT', function () {
    /** @var AggregateRootTestCase $this
    $nftId = $this->aggregateRootId();

    $event1 = new NftAcquired($nftId, new FiatAmount('100', Currency::GBP));
    $action = new AverageCostBasis($nftId, new FiatAmount('50', Currency::GBP));
    $event2 = new CostBasisAveraged($nftId, $action->costBasis);

    $this->given($event1)
        ->when($action)
        ->then($event2);
});
*/

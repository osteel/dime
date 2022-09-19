<?php

use Domain\Tests\Aggregates\NftTestCase;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(NftTestCase::class);

it('it can acquire an NFT', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $action = new Acquire($nftId, new Fiat('100', Currency::GBP()));
    $event = new Acquired($action->nftId, $action->costBasis);

    $this->when($action)
        ->then($event);
});

it('it cannot acquire the same NFT several times', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $event = new Acquired($nftId, new Fiat('100', Currency::GBP()));
    $action = new Acquire($nftId, new Fiat('50', Currency::GBP()));
    $exception = NftException::alreadyAcquired($action->id);

    $this->given($event)
        ->when($action)
        ->expectToFail($exception);
});

it('it can average the cost basis of an NFT', function () {
    /** @var AggregateRootTestCase $this */
    $nftId = $this->aggregateRootId();

    $event1 = new Acquired($nftId, new Fiat('100', Currency::GBP()));
    $action = new AverageCostBasis($nftId, new Fiat('50', Currency::GBP()));
    $event2 = new CostBasisAveraged($nftId, $action->costBasis);

    $this->given($event1)
        ->when($action)
        ->then($event2);
});

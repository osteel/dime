<?php

use Domain\Enums\Currency;
use Domain\Section104Pool\Actions\AcquireSection104PoolTokens;
use Domain\Section104Pool\Events\Section104PoolTokensAcquired;
use Domain\Tests\Section104Pool\Section104PoolTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(Section104PoolTestCase::class);

beforeEach(function () {
    $this->section104PoolId = $this->aggregateRootId();
});

it('can acquire some section 104 pool tokens', function () {
    $acquireSection104PoolTokens = new AcquireSection104PoolTokens(
        section104PoolId: $this->section104PoolId,
        quantity: '100',
        costBasis: new FiatAmount('100', Currency::GBP),
    );

    $section104PoolTokensAcquired = new Section104PoolTokensAcquired(
        section104PoolId: $acquireSection104PoolTokens->section104PoolId,
        previousQuantity: '0',
        extraQuantity: '100',
        newQuantity: '100',
        previousCostBasis: new FiatAmount('0', Currency::GBP),
        extraCostBasis: $acquireSection104PoolTokens->costBasis,
        newCostBasis: new FiatAmount('100', Currency::GBP),
        previousAverageCostBasisPerUnit: new FiatAmount('0', Currency::GBP),
        newAverageCostBasisPerUnit: new FiatAmount('1', Currency::GBP),
    );

    /** @var AggregateRootTestCase $this */
    $this->when($acquireSection104PoolTokens)
        ->then($section104PoolTokensAcquired);
});

it('can acquire more of the same section 104 pool tokens', function () {
    $someSection104PoolTokensAcquired = new Section104PoolTokensAcquired(
        section104PoolId: $this->section104PoolId,
        previousQuantity: '0',
        extraQuantity: '100',
        newQuantity: '100',
        previousCostBasis: new FiatAmount('0', Currency::GBP),
        extraCostBasis: new FiatAmount('100', Currency::GBP),
        newCostBasis: new FiatAmount('100', Currency::GBP),
        previousAverageCostBasisPerUnit: new FiatAmount('0', Currency::GBP),
        newAverageCostBasisPerUnit: new FiatAmount('1', Currency::GBP),
    );

    $moreSection104PoolTokensAcquired = new Section104PoolTokensAcquired(
        section104PoolId: $this->section104PoolId,
        previousQuantity: '100',
        extraQuantity: '300',
        newQuantity: '400',
        previousCostBasis: new FiatAmount('100', Currency::GBP),
        extraCostBasis: new FiatAmount('400', Currency::GBP),
        newCostBasis: new FiatAmount('500', Currency::GBP),
        previousAverageCostBasisPerUnit: new FiatAmount('1', Currency::GBP),
        newAverageCostBasisPerUnit: new FiatAmount('1.25', Currency::GBP),
    );

    $acquireEvenMoreSection104PoolTokens = new AcquireSection104PoolTokens(
        section104PoolId: $this->section104PoolId,
        quantity: '100',
        costBasis: new FiatAmount('300', Currency::GBP),
    );

    $evenMoreSection104PoolTokensAcquired = new Section104PoolTokensAcquired(
        section104PoolId: $acquireEvenMoreSection104PoolTokens->section104PoolId,
        previousQuantity: '400',
        extraQuantity: '100',
        newQuantity: '500',
        previousCostBasis: new FiatAmount('500', Currency::GBP),
        extraCostBasis: $acquireEvenMoreSection104PoolTokens->costBasis,
        newCostBasis: new FiatAmount('800', Currency::GBP),
        previousAverageCostBasisPerUnit: new FiatAmount('1.25', Currency::GBP),
        newAverageCostBasisPerUnit: new FiatAmount('1.6', Currency::GBP),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSection104PoolTokensAcquired, $moreSection104PoolTokensAcquired)
        ->when($acquireEvenMoreSection104PoolTokens)
        ->then($evenMoreSection104PoolTokensAcquired);
});

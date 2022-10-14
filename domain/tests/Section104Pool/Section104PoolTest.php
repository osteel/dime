<?php

use Domain\Enums\Currency;
use Domain\Section104Pool\Actions\AcquireSection104PoolTokens;
use Domain\Section104Pool\Actions\DisposeOfSection104PoolTokens;
use Domain\Section104Pool\Events\Section104PoolTokensAcquired;
use Domain\Section104Pool\Events\Section104PoolTokensDisposedOf;
use Domain\Section104Pool\Exceptions\Section104PoolException;
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
        acquiredQuantity: '100',
        newQuantity: '100',
        previousCostBasis: new FiatAmount('0', Currency::GBP),
        acquisitionCostBasis: $acquireSection104PoolTokens->costBasis,
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
        acquiredQuantity: '100',
        newQuantity: '100',
        previousCostBasis: new FiatAmount('0', Currency::GBP),
        acquisitionCostBasis: new FiatAmount('100', Currency::GBP),
        newCostBasis: new FiatAmount('100', Currency::GBP),
        previousAverageCostBasisPerUnit: new FiatAmount('0', Currency::GBP),
        newAverageCostBasisPerUnit: new FiatAmount('1', Currency::GBP),
    );

    $moreSection104PoolTokensAcquired = new Section104PoolTokensAcquired(
        section104PoolId: $someSection104PoolTokensAcquired->section104PoolId,
        previousQuantity: $someSection104PoolTokensAcquired->newQuantity,
        acquiredQuantity: '300',
        newQuantity: '400',
        previousCostBasis: $someSection104PoolTokensAcquired->newCostBasis,
        acquisitionCostBasis: new FiatAmount('400', Currency::GBP),
        newCostBasis: new FiatAmount('500', Currency::GBP),
        previousAverageCostBasisPerUnit: $someSection104PoolTokensAcquired->newAverageCostBasisPerUnit,
        newAverageCostBasisPerUnit: new FiatAmount('1.25', Currency::GBP),
    );

    $acquireEvenMoreSection104PoolTokens = new AcquireSection104PoolTokens(
        section104PoolId: $moreSection104PoolTokensAcquired->section104PoolId,
        quantity: '100',
        costBasis: new FiatAmount('300', Currency::GBP),
    );

    $evenMoreSection104PoolTokensAcquired = new Section104PoolTokensAcquired(
        section104PoolId: $acquireEvenMoreSection104PoolTokens->section104PoolId,
        previousQuantity: $moreSection104PoolTokensAcquired->newQuantity,
        acquiredQuantity: '100',
        newQuantity: '500',
        previousCostBasis: $moreSection104PoolTokensAcquired->newCostBasis,
        acquisitionCostBasis: $acquireEvenMoreSection104PoolTokens->costBasis,
        newCostBasis: new FiatAmount('800', Currency::GBP),
        previousAverageCostBasisPerUnit: $moreSection104PoolTokensAcquired->newAverageCostBasisPerUnit,
        newAverageCostBasisPerUnit: new FiatAmount('1.6', Currency::GBP),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSection104PoolTokensAcquired, $moreSection104PoolTokensAcquired)
        ->when($acquireEvenMoreSection104PoolTokens)
        ->then($evenMoreSection104PoolTokensAcquired);
});

it('cannot acquire more of the same section 104 pool tokens because the currency is different', function () {
    $someSection104PoolTokensAcquired = new Section104PoolTokensAcquired(
        section104PoolId: $this->section104PoolId,
        previousQuantity: '0',
        acquiredQuantity: '100',
        newQuantity: '100',
        previousCostBasis: new FiatAmount('0', Currency::GBP),
        acquisitionCostBasis: new FiatAmount('100', Currency::GBP),
        newCostBasis: new FiatAmount('100', Currency::GBP),
        previousAverageCostBasisPerUnit: new FiatAmount('0', Currency::GBP),
        newAverageCostBasisPerUnit: new FiatAmount('1', Currency::GBP),
    );

    $acquireMoreSection104PoolTokens = new AcquireSection104PoolTokens(
        section104PoolId: $someSection104PoolTokensAcquired->section104PoolId,
        quantity: '100',
        costBasis: new FiatAmount('300', Currency::EUR),
    );

    $cannotAcquireSection104PoolTokens = Section104PoolException::cannotAcquireDifferentCostBasisCurrency(
        section104PoolId: $someSection104PoolTokensAcquired->section104PoolId,
        from: Currency::GBP,
        to: Currency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSection104PoolTokensAcquired)
        ->when($acquireMoreSection104PoolTokens)
        ->expectToFail($cannotAcquireSection104PoolTokens);
});

it('can dispose of some section 104 pool tokens', function () {
    $section104PoolTokensAcquired = new Section104PoolTokensAcquired(
        section104PoolId: $this->section104PoolId,
        previousQuantity: '0',
        acquiredQuantity: '100',
        newQuantity: '100',
        previousCostBasis: new FiatAmount('0', Currency::GBP),
        acquisitionCostBasis: new FiatAmount('200', Currency::GBP),
        newCostBasis: new FiatAmount('200', Currency::GBP),
        previousAverageCostBasisPerUnit: new FiatAmount('0', Currency::GBP),
        newAverageCostBasisPerUnit: new FiatAmount('2', Currency::GBP),
    );

    $disposeOfSection104PoolTokens = new DisposeOfSection104PoolTokens(
        section104PoolId: $section104PoolTokensAcquired->section104PoolId,
        quantity: '50',
        disposalProceeds: new FiatAmount('100', Currency::GBP),
    );

    $section104PoolTokensDisposedOf = new Section104PoolTokensDisposedOf(
        section104PoolId: $disposeOfSection104PoolTokens->section104PoolId,
        previousQuantity: $section104PoolTokensAcquired->newQuantity,
        disposedOfQuantity: '50',
        newQuantity: '50',
        previousCostBasis: $section104PoolTokensAcquired->newCostBasis,
        averageCostBasisPerUnit: $section104PoolTokensAcquired->newAverageCostBasisPerUnit,
        newCostBasis: new FiatAmount('100', Currency::GBP),
        disposalProceeds: new FiatAmount('100', Currency::GBP),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($section104PoolTokensAcquired)
        ->when($disposeOfSection104PoolTokens)
        ->then($section104PoolTokensDisposedOf);
});

it('cannot dispose of some section 104 pool tokens because the quantity is too high', function () {
    $section104PoolTokensAcquired = new Section104PoolTokensAcquired(
        section104PoolId: $this->section104PoolId,
        previousQuantity: '0',
        acquiredQuantity: '100',
        newQuantity: '100',
        previousCostBasis: new FiatAmount('0', Currency::GBP),
        acquisitionCostBasis: new FiatAmount('100', Currency::GBP),
        newCostBasis: new FiatAmount('100', Currency::GBP),
        previousAverageCostBasisPerUnit: new FiatAmount('0', Currency::GBP),
        newAverageCostBasisPerUnit: new FiatAmount('1', Currency::GBP),
    );

    $disposeOfSection104PoolTokens = new DisposeOfSection104PoolTokens(
        section104PoolId: $section104PoolTokensAcquired->section104PoolId,
        quantity: '101',
        disposalProceeds: new FiatAmount('100', Currency::GBP),
    );

    $cannotDisposeOfSection104PoolTokens = Section104PoolException::disposalQuantityIsTooHigh(
        section104PoolId: $section104PoolTokensAcquired->section104PoolId,
        disposalQuantity: $disposeOfSection104PoolTokens->quantity,
        availableQuantity: '100',
    );

    /** @var AggregateRootTestCase $this */
    $this->given($section104PoolTokensAcquired)
        ->when($disposeOfSection104PoolTokens)
        ->expectToFail($cannotDisposeOfSection104PoolTokens);
});

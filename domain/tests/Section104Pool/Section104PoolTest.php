<?php

use Domain\Enums\Currency;
use Domain\Section104Pool\Actions\AcquireSection104PoolToken;
use Domain\Section104Pool\Actions\DisposeOfSection104PoolToken;
use Domain\Section104Pool\Events\Section104PoolTokenAcquired;
use Domain\Section104Pool\Events\Section104PoolTokenDisposedOf;
use Domain\Section104Pool\Exceptions\Section104PoolException;
use Domain\Tests\Section104Pool\Section104PoolTestCase;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(Section104PoolTestCase::class);

beforeEach(function () {
    $this->section104PoolId = $this->aggregateRootId();
});

it('can acquire some section 104 pool tokens', function () {
    $acquireSection104PoolToken = new AcquireSection104PoolToken(
        section104PoolId: $this->section104PoolId,
        quantity: '100',
        costBasis: new FiatAmount('100', Currency::GBP),
    );

    $section104PoolTokensAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $acquireSection104PoolToken->section104PoolId,
        previousQuantity: '0',
        acquiredQuantity: '100',
        newQuantity: '100',
        previousCostBasis: new FiatAmount('0', Currency::GBP),
        acquisitionCostBasis: $acquireSection104PoolToken->costBasis,
        newCostBasis: new FiatAmount('100', Currency::GBP),
        previousAverageCostBasisPerUnit: new FiatAmount('0', Currency::GBP),
        newAverageCostBasisPerUnit: new FiatAmount('1', Currency::GBP),
    );

    /** @var AggregateRootTestCase $this */
    $this->when($acquireSection104PoolToken)
        ->then($section104PoolTokensAcquired);
});

it('can acquire more of the same section 104 pool tokens', function () {
    $someSection104PoolTokenAcquired = new Section104PoolTokenAcquired(
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

    $moreSection104PoolTokenAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $someSection104PoolTokenAcquired->section104PoolId,
        previousQuantity: $someSection104PoolTokenAcquired->newQuantity,
        acquiredQuantity: '300',
        newQuantity: '400',
        previousCostBasis: $someSection104PoolTokenAcquired->newCostBasis,
        acquisitionCostBasis: new FiatAmount('400', Currency::GBP),
        newCostBasis: new FiatAmount('500', Currency::GBP),
        previousAverageCostBasisPerUnit: $someSection104PoolTokenAcquired->newAverageCostBasisPerUnit,
        newAverageCostBasisPerUnit: new FiatAmount('1.25', Currency::GBP),
    );

    $acquireEvenMoreSection104PoolToken = new AcquireSection104PoolToken(
        section104PoolId: $moreSection104PoolTokenAcquired->section104PoolId,
        quantity: '100',
        costBasis: new FiatAmount('300', Currency::GBP),
    );

    $evenMoreSection104PoolTokenAcquired = new Section104PoolTokenAcquired(
        section104PoolId: $acquireEvenMoreSection104PoolToken->section104PoolId,
        previousQuantity: $moreSection104PoolTokenAcquired->newQuantity,
        acquiredQuantity: '100',
        newQuantity: '500',
        previousCostBasis: $moreSection104PoolTokenAcquired->newCostBasis,
        acquisitionCostBasis: $acquireEvenMoreSection104PoolToken->costBasis,
        newCostBasis: new FiatAmount('800', Currency::GBP),
        previousAverageCostBasisPerUnit: $moreSection104PoolTokenAcquired->newAverageCostBasisPerUnit,
        newAverageCostBasisPerUnit: new FiatAmount('1.6', Currency::GBP),
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSection104PoolTokenAcquired, $moreSection104PoolTokenAcquired)
        ->when($acquireEvenMoreSection104PoolToken)
        ->then($evenMoreSection104PoolTokenAcquired);
});

it('cannot acquire more of the same section 104 pool tokens because the currency is different', function () {
    $someSection104PoolTokenAcquired = new Section104PoolTokenAcquired(
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

    $acquireMoreSection104PoolToken = new AcquireSection104PoolToken(
        section104PoolId: $someSection104PoolTokenAcquired->section104PoolId,
        quantity: '100',
        costBasis: new FiatAmount('300', Currency::EUR),
    );

    $cannotAcquireSection104PoolToken = Section104PoolException::cannotAcquireDifferentCostBasisCurrency(
        section104PoolId: $someSection104PoolTokenAcquired->section104PoolId,
        from: Currency::GBP,
        to: Currency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($someSection104PoolTokenAcquired)
        ->when($acquireMoreSection104PoolToken)
        ->expectToFail($cannotAcquireSection104PoolToken);
});

it('can dispose of some section 104 pool tokens', function () {
    $section104PoolTokensAcquired = new Section104PoolTokenAcquired(
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

    $disposeOfSection104PoolToken = new DisposeOfSection104PoolToken(
        section104PoolId: $section104PoolTokensAcquired->section104PoolId,
        quantity: '50',
        disposalProceeds: new FiatAmount('100', Currency::GBP),
    );

    $section104PoolTokensDisposedOf = new Section104PoolTokenDisposedOf(
        section104PoolId: $disposeOfSection104PoolToken->section104PoolId,
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
        ->when($disposeOfSection104PoolToken)
        ->then($section104PoolTokensDisposedOf);
});

it('cannot dispose of some section 104 pool tokens because the quantity is too high', function () {
    $section104PoolTokensAcquired = new Section104PoolTokenAcquired(
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

    $disposeOfSection104PoolToken = new DisposeOfSection104PoolToken(
        section104PoolId: $section104PoolTokensAcquired->section104PoolId,
        quantity: '101',
        disposalProceeds: new FiatAmount('100', Currency::GBP),
    );

    $cannotDisposeOfSection104PoolToken = Section104PoolException::disposalQuantityIsTooHigh(
        section104PoolId: $section104PoolTokensAcquired->section104PoolId,
        disposalQuantity: $disposeOfSection104PoolToken->quantity,
        availableQuantity: '100',
    );

    /** @var AggregateRootTestCase $this */
    $this->given($section104PoolTokensAcquired)
        ->when($disposeOfSection104PoolToken)
        ->expectToFail($cannotDisposeOfSection104PoolToken);
});

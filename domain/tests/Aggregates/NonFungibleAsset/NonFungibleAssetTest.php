<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\IncreaseNonFungibleAssetCostBasis;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetAcquired;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetCostBasisIncreased;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetDisposedOf;
use Domain\Aggregates\NonFungibleAsset\Exceptions\NonFungibleAssetException;
use Domain\Enums\FiatCurrency;
use Domain\Tests\Aggregates\NonFungibleAsset\NonFungibleAssetTestCase;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(NonFungibleAssetTestCase::class);

beforeEach(function () {
    $this->asset = new Asset(symbol: 'foo', isNonFungible: true);
});

it('can acquire a non-fungible asset', function () {
    // When

    $acquireNonFungibleAsset = new AcquireNonFungibleAsset(
        asset: $this->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    );

    // Then

    $nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        asset: $acquireNonFungibleAsset->asset,
        date: $acquireNonFungibleAsset->date,
        costBasis: $acquireNonFungibleAsset->costBasis,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($acquireNonFungibleAsset)
        ->then($nonFungibleAssetAcquired);
});

it('cannot acquire the same non-fungible asset more than once', function () {
    // Given

    $nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        asset: $this->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    );

    // When

    $acquireSameNonFungibleAsset = new AcquireNonFungibleAsset(
        asset: $nonFungibleAssetAcquired->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    );

    // Then

    $alreadyAcquired = NonFungibleAssetException::alreadyAcquired($this->asset);

    /** @var AggregateRootTestCase $this */
    $this->given($nonFungibleAssetAcquired)
        ->when($acquireSameNonFungibleAsset)
        ->expectToFail($alreadyAcquired);
});

it('can increase the cost basis of a non-fungible asset', function () {
    // Given

    $nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        asset: $this->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    );

    // When

    $increaseNonFungibleAssetCostBasis = new IncreaseNonFungibleAssetCostBasis(
        asset: $nonFungibleAssetAcquired->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: FiatAmount::GBP('50'),
    );

    // Then

    $nonFungibleAssetCostBasisIncreased = new NonFungibleAssetCostBasisIncreased(
        asset: $increaseNonFungibleAssetCostBasis->asset,
        date: $increaseNonFungibleAssetCostBasis->date,
        costBasisIncrease: $increaseNonFungibleAssetCostBasis->costBasisIncrease,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nonFungibleAssetAcquired)
        ->when($increaseNonFungibleAssetCostBasis)
        ->then($nonFungibleAssetCostBasisIncreased);
});

it('cannot increase the cost basis of a non-fungible asset that has not been acquired', function () {
    // When

    $increaseNonFungibleAssetCostBasis = new IncreaseNonFungibleAssetCostBasis(
        asset: $this->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: FiatAmount::GBP('100'),
    );

    // Then

    $cannotIncreaseCostBasis = NonFungibleAssetException::cannotIncreaseCostBasisBeforeAcquisition($this->asset);

    /** @var AggregateRootTestCase $this */
    $this->when($increaseNonFungibleAssetCostBasis)
        ->expectToFail($cannotIncreaseCostBasis);
});

it('cannot increase the cost basis of a non-fungible asset because the transaction is older than the previous one', function () {
    // Given

    $nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        asset: $this->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    );

    // When

    $increaseNonFungibleAssetCostBasis = new IncreaseNonFungibleAssetCostBasis(
        asset: $nonFungibleAssetAcquired->asset,
        date: LocalDate::parse('2015-10-20'),
        costBasisIncrease: FiatAmount::GBP('100'),
    );

    // Then

    $cannotIncreaseCostBasis = NonFungibleAssetException::olderThanPreviousTransaction(
        action: $increaseNonFungibleAssetCostBasis,
        previousTransactionDate: $nonFungibleAssetAcquired->date,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nonFungibleAssetAcquired)
        ->when($increaseNonFungibleAssetCostBasis)
        ->expectToFail($cannotIncreaseCostBasis);
});

it('cannot increase the cost basis of a non-fungible asset because the currency is different', function () {
    // Given

    $nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        asset: $this->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    );

    // When

    $increaseNonFungibleAssetCostBasis = new IncreaseNonFungibleAssetCostBasis(
        asset: $nonFungibleAssetAcquired->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: new FiatAmount('100', FiatCurrency::EUR),
    );

    // Then

    $cannotIncreaseCostBasis = NonFungibleAssetException::currencyMismatch(
        action: $increaseNonFungibleAssetCostBasis,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nonFungibleAssetAcquired)
        ->when($increaseNonFungibleAssetCostBasis)
        ->expectToFail($cannotIncreaseCostBasis);
});

it('can dispose of a non-fungible asset', function () {
    // Given

    $nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        asset: $this->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    );

    // When

    $disposeOfNonFungibleAsset = new DisposeOfNonFungibleAsset(
        asset: $nonFungibleAssetAcquired->asset,
        date: LocalDate::parse('2015-10-21'),
        proceeds: FiatAmount::GBP('150'),
    );

    // Then

    $nonFungibleAssetDisposedOf = new NonFungibleAssetDisposedOf(
        asset: $disposeOfNonFungibleAsset->asset,
        date: $disposeOfNonFungibleAsset->date,
        costBasis: $nonFungibleAssetAcquired->costBasis,
        proceeds: $disposeOfNonFungibleAsset->proceeds,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nonFungibleAssetAcquired)
        ->when($disposeOfNonFungibleAsset)
        ->then($nonFungibleAssetDisposedOf);
});

it('cannot dispose of a non-fungible asset that has not been acquired', function () {
    // When

    $disposeOfNonFungibleAsset = new DisposeOfNonFungibleAsset(
        asset: $this->asset,
        date: LocalDate::parse('2015-10-21'),
        proceeds: FiatAmount::GBP('100'),
    );

    // Then

    $cannotDisposeOf = NonFungibleAssetException::cannotDisposeOfBeforeAcquisition($this->asset);

    /** @var AggregateRootTestCase $this */
    $this->when($disposeOfNonFungibleAsset)
        ->expectToFail($cannotDisposeOf);
});

it('cannot dispose of a non-fungible asset because the transaction is older than the previous one', function () {
    // Given

    $nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        asset: $this->asset,
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    );

    // When

    $disposeOfNonFungibleAsset = new DisposeOfNonFungibleAsset(
        asset: $nonFungibleAssetAcquired->asset,
        date: LocalDate::parse('2015-10-20'),
        proceeds: FiatAmount::GBP('150'),
    );

    // Then

    $cannotDisposeOf = NonFungibleAssetException::olderThanPreviousTransaction(
        action: $disposeOfNonFungibleAsset,
        previousTransactionDate: $nonFungibleAssetAcquired->date,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nonFungibleAssetAcquired)
        ->when($disposeOfNonFungibleAsset)
        ->expectToFail($cannotDisposeOf);
});

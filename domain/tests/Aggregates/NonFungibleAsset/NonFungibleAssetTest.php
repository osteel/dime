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

use function EventSauce\EventSourcing\PestTooling\expectToFail;
use function EventSauce\EventSourcing\PestTooling\given;
use function EventSauce\EventSourcing\PestTooling\then;
use function EventSauce\EventSourcing\PestTooling\when;

uses(NonFungibleAssetTestCase::class);

it('can acquire a non-fungible asset', function () {
    when($acquireNonFungibleAsset = new AcquireNonFungibleAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    ));

    then(new NonFungibleAssetAcquired(
        date: $acquireNonFungibleAsset->date,
        costBasis: $acquireNonFungibleAsset->costBasis,
    ));
});

it('cannot acquire a non-fungible asset because the asset is fungible', function () {
    when($acquireNonFungibleAsset = new AcquireNonFungibleAsset(
        asset: new Asset('FOO'),
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    ));

    expectToFail(NonFungibleAssetException::assetIsFungible($acquireNonFungibleAsset));
});

it('cannot acquire the same non-fungible asset more than once', function () {
    given(new NonFungibleAssetAcquired(date: LocalDate::parse('2015-10-21'), costBasis: FiatAmount::GBP('100')));

    when(new AcquireNonFungibleAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    ));

    expectToFail(NonFungibleAssetException::alreadyAcquired($this->aggregateRootId->toAsset()));
});

it('can increase the cost basis of a non-fungible asset', function () {
    given(new NonFungibleAssetAcquired(date: LocalDate::parse('2015-10-21'), costBasis: FiatAmount::GBP('100')));

    when($increaseNonFungibleAssetCostBasis = new IncreaseNonFungibleAssetCostBasis(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: FiatAmount::GBP('50'),
    ));

    then(new NonFungibleAssetCostBasisIncreased(
        date: $increaseNonFungibleAssetCostBasis->date,
        costBasisIncrease: $increaseNonFungibleAssetCostBasis->costBasisIncrease,
        newCostBasis: FiatAmount::GBP('150'),
    ));
});

it('cannot increase the cost basis of a non-fungible asset that has not been acquired', function () {
    when(new IncreaseNonFungibleAssetCostBasis(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: FiatAmount::GBP('100'),
    ));

    expectToFail(NonFungibleAssetException::cannotIncreaseCostBasisBeforeAcquisition($this->aggregateRootId->toAsset()));
});

it('cannot increase the cost basis of a non-fungible asset because the transaction is older than the previous one', function () {
    given($nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    ));

    when($increaseNonFungibleAssetCostBasis = new IncreaseNonFungibleAssetCostBasis(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-20'),
        costBasisIncrease: FiatAmount::GBP('100'),
    ));

    expectToFail(NonFungibleAssetException::olderThanPreviousTransaction(
        action: $increaseNonFungibleAssetCostBasis,
        previousTransactionDate: $nonFungibleAssetAcquired->date,
    ));
});

it('cannot increase the cost basis of a non-fungible asset because the currency is different', function () {
    given(new NonFungibleAssetAcquired(date: LocalDate::parse('2015-10-21'), costBasis: FiatAmount::GBP('100')));

    when($increaseNonFungibleAssetCostBasis = new IncreaseNonFungibleAssetCostBasis(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: new FiatAmount('100', FiatCurrency::EUR),
    ));

    expectToFail(NonFungibleAssetException::currencyMismatch(
        action: $increaseNonFungibleAssetCostBasis,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    ));
});

it('can dispose of a non-fungible asset', function () {
    given($nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    ));

    when($disposeOfNonFungibleAsset = new DisposeOfNonFungibleAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        proceeds: FiatAmount::GBP('150'),
    ));

    then(new NonFungibleAssetDisposedOf(
        date: $disposeOfNonFungibleAsset->date,
        costBasis: $nonFungibleAssetAcquired->costBasis,
        proceeds: $disposeOfNonFungibleAsset->proceeds,
    ));
});

it('cannot dispose of a non-fungible asset that has not been acquired', function () {
    when(new DisposeOfNonFungibleAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        proceeds: FiatAmount::GBP('100'),
    ));

    expectToFail(NonFungibleAssetException::cannotDisposeOfBeforeAcquisition($this->aggregateRootId->toAsset()));
});

it('cannot dispose of a non-fungible asset because the currencies don\'t match', function () {
    given(new NonFungibleAssetAcquired(date: LocalDate::parse('2015-10-21'), costBasis: FiatAmount::GBP('100')));

    when($disposeOfNonFungibleAsset = new DisposeOfNonFungibleAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-21'),
        proceeds: new FiatAmount('100', FiatCurrency::EUR),
    ));

    expectToFail(NonFungibleAssetException::currencyMismatch(
        action: $disposeOfNonFungibleAsset,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    ));
});

it('cannot dispose of a non-fungible asset because the transaction is older than the previous one', function () {
    given($nonFungibleAssetAcquired = new NonFungibleAssetAcquired(
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('100'),
    ));

    when($disposeOfNonFungibleAsset = new DisposeOfNonFungibleAsset(
        asset: $this->aggregateRootId->toAsset(),
        date: LocalDate::parse('2015-10-20'),
        proceeds: FiatAmount::GBP('150'),
    ));

    expectToFail(NonFungibleAssetException::olderThanPreviousTransaction(
        action: $disposeOfNonFungibleAsset,
        previousTransactionDate: $nonFungibleAssetAcquired->date,
    ));
});

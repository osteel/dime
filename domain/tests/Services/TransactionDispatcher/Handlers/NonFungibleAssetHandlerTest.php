<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\IncreaseNonFungibleAssetCostBasis;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAsset;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\NonFungibleAssetHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\NonFungibleAssetHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;

beforeEach(function () {
    $this->nonFungibleAssetRepository = Mockery::mock(NonFungibleAssetRepository::class);
    $this->nonFungibleAssetHandler = new NonFungibleAssetHandler($this->nonFungibleAssetRepository);
});

it('can handle a receive operation', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    /** @var Transaction */
    $transaction = Acquisition::factory()->nonFungibleAsset()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $nonFungibleAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a receive operation with a fee', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    /** @var Transaction */
    $transaction = Acquisition::factory()
        ->nonFungibleAsset()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $nonFungibleAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();
});

it('can handle a send operation', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    $transaction = Disposal::factory()->nonFungibleAsset()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $nonFungibleAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a send operation with a fee', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    $transaction = Disposal::factory()
        ->nonFungibleAsset()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $nonFungibleAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();
});

it('can handle a swap operation where the received asset is a non-fungible asset', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    $transaction = Swap::factory()->toNonFungibleAsset()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $nonFungibleAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where the sent asset is a non-fungible asset', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    $transaction = Swap::factory()->fromNonFungibleAsset()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $nonFungibleAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where both assets are non-fungible assets', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->twice()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->twice()->with($nonFungibleAsset);

    $transaction = Swap::factory()->nonFungibleAssets()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $nonFungibleAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $nonFungibleAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where both assets are non-fungible assets and the received non-fungible asset is already acquired', function () {
    $nonFungibleAsset = Mockery::mock(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->twice()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->twice()->with($nonFungibleAsset);

    $transaction = Swap::factory()->nonFungibleAssets()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $nonFungibleAsset->shouldReceive('isAlreadyAcquired')->andReturn(true)->once();

    $nonFungibleAsset->shouldReceive(
        'disposeOf',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $nonFungibleAsset->shouldReceive(
        'increaseCostBasis',
        fn (IncreaseNonFungibleAssetCostBasis $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasisIncrease->isEqualTo($transaction->marketValue),
    )->once();

    $this->nonFungibleAssetHandler->handle($transaction);
});

it('can handle a swap operation with a fee', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->twice()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->twice()->with($nonFungibleAsset);

    $transaction = Swap::factory()
        ->nonFungibleAssets()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $nonFungibleAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(FiatAmount::GBP('45')),
    )->once();

    $nonFungibleAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(FiatAmount::GBP('55')),
    )->once();
});

it('can handle a swap operation with a fee where the received asset is a non-fungible asset and the sent asset is some fiat currency', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    $transaction = Swap::factory()
        ->toNonFungibleAsset()
        ->fromFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $nonFungibleAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();
});

it('can handle a swap operation with a fee where the sent asset is a non-fungible asset and the received asset is some fiat currency', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);

    $this->nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $this->nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    $transaction = Swap::factory()
        ->fromNonFungibleAsset()
        ->toFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $nonFungibleAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();
});

it('cannot handle a transaction because none of the assets is a non-fungible asset', function () {
    $transaction = Swap::factory()->make();

    expect(fn () => $this->nonFungibleAssetHandler->handle($transaction))
        ->toThrow(NonFungibleAssetHandlerException::class, NonFungibleAssetHandlerException::noNonFungibleAsset($transaction)->getMessage());
});

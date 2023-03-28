<?php

use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Repositories\SharePoolingAssetRepository;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAsset;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\SharePoolingAssetHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\SharePoolingAssetHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;

beforeEach(function () {
    $this->sharePoolingAssetRepository = Mockery::mock(SharePoolingAssetRepository::class);
    $this->sharePoolingAssetHandler = new SharePoolingAssetHandler($this->sharePoolingAssetRepository);
    $this->sharePoolingAsset = Mockery::spy(SharePoolingAsset::class);
});

it('can handle a receive operation', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->once()->with($this->sharePoolingAsset);

    $transaction = Acquisition::factory()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a receive operation with a fee', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->once()->with($this->sharePoolingAsset);

    $transaction = Acquisition::factory()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();
});

it('can handle a send operation', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->once()->with($this->sharePoolingAsset);

    $transaction = Disposal::factory()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a send operation with a fee', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->once()->with($this->sharePoolingAsset);

    $transaction = Disposal::factory()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();
});

it('can handle a swap operation where the received asset is not a non-fungible asset', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->once()->with($this->sharePoolingAsset);

    $transaction = Swap::factory()->fromNonFungibleAsset()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where the sent asset is not a non-fungible asset', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->once()->with($this->sharePoolingAsset);

    $transaction = Swap::factory()->toNonFungibleAsset()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where neither asset is a non-fungible asset', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->twice()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->twice()->with($this->sharePoolingAsset);

    $transaction = Swap::factory()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $this->sharePoolingAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where the received asset is some fiat currency', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->once()->with($this->sharePoolingAsset);

    $transaction = Swap::factory()->toFiat()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $this->sharePoolingAsset->shouldNotHaveReceived('acquire');
});

it('can handle a swap operation where the sent asset is some fiat currency', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->once()->with($this->sharePoolingAsset);

    $transaction = Swap::factory()->fromFiat()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();

    $this->sharePoolingAsset->shouldNotHaveReceived('disposeOf');
});

it('can handle a swap operation with a fee', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->twice()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->twice()->with($this->sharePoolingAsset);

    $transaction = Swap::factory()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo(FiatAmount::GBP('45')),
    )->once();

    $this->sharePoolingAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo(FiatAmount::GBP('55')),
    )->once();
});

it('can handle a swap operation with a fee where the received asset is some fiat currency', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->once()->with($this->sharePoolingAsset);

    $transaction = Swap::factory()
        ->toFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'disposeOf',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();

    $this->sharePoolingAsset->shouldNotHaveReceived('acquire');
});

it('can handle a swap operation with a fee where the sent asset is some fiat currency', function () {
    $this->sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($this->sharePoolingAsset);
    $this->sharePoolingAssetRepository->shouldReceive('save')->once()->with($this->sharePoolingAsset);

    $transaction = Swap::factory()
        ->fromFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->sharePoolingAsset->shouldHaveReceived(
        'acquire',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();

    $this->sharePoolingAsset->shouldNotHaveReceived('disposeOf');
});

it('cannot handle a transaction because one of the assets is a non-fungible asset', function () {
    $transaction = Swap::factory()->nonFungibleAssets()->make();

    expect(fn () => $this->sharePoolingAssetHandler->handle($transaction))
        ->toThrow(SharePoolingAssetHandlerException::class, SharePoolingAssetHandlerException::noSharePoolingAsset($transaction)->getMessage());
});

<?php

use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAsset;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\SharePoolingAssetHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\SharePoolingAssetHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;
use Illuminate\Contracts\Bus\Dispatcher;

beforeEach(function () {
    $this->dispatcher = Mockery::spy(Dispatcher::class);
    $this->sharePoolingAssetHandler = new SharePoolingAssetHandler($this->dispatcher);
    $this->sharePoolingAsset = Mockery::spy(SharePoolingAsset::class);
});

it('can handle a receive operation', function () {
    $transaction = Acquisition::factory()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a receive operation with a fee', function () {
    $transaction = Acquisition::factory()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();
});

it('can handle a send operation', function () {
    $transaction = Disposal::factory()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a send operation with a fee', function () {
    $transaction = Disposal::factory()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();
});

it('can handle a swap operation where the received asset is not a non-fungible asset', function () {
    $transaction = Swap::factory()->fromNonFungibleAsset()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where the sent asset is not a non-fungible asset', function () {
    $transaction = Swap::factory()->toNonFungibleAsset()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where neither asset is a non-fungible asset', function () {
    $transaction = Swap::factory()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (object $action) => $action instanceof DisposeOfSharePoolingAsset
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (object $action) => $action instanceof AcquireSharePoolingAsset
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where the received asset is some fiat currency', function () {
    $transaction = Swap::factory()->toFiat()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $this->sharePoolingAsset->shouldNotHaveReceived('acquire');
});

it('can handle a swap operation where the sent asset is some fiat currency', function () {
    $transaction = Swap::factory()->fromFiat()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();

    $this->sharePoolingAsset->shouldNotHaveReceived('disposeOf');
});

it('can handle a swap operation with a fee', function () {
    $transaction = Swap::factory()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (object $action) => $action instanceof DisposeOfSharePoolingAsset
            && $action->proceeds->isEqualTo(FiatAmount::GBP('45')),
    )->once();

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (object $action) => $action instanceof AcquireSharePoolingAsset
            && $action->costBasis->isEqualTo(FiatAmount::GBP('55')),
    )->once();
});

it('can handle a swap operation with a fee where the received asset is some fiat currency', function () {
    $transaction = Swap::factory()
        ->toFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (DisposeOfSharePoolingAsset $action) => $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();

    $this->sharePoolingAsset->shouldNotHaveReceived('acquire');
});

it('can handle a swap operation with a fee where the sent asset is some fiat currency', function () {
    $transaction = Swap::factory()
        ->fromFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->sharePoolingAssetHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (AcquireSharePoolingAsset $action) => $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();

    $this->sharePoolingAsset->shouldNotHaveReceived('disposeOf');
});

it('cannot handle a transaction because one of the assets is a non-fungible asset', function () {
    $transaction = Swap::factory()->nonFungibleAssets()->make();

    expect(fn () => $this->sharePoolingAssetHandler->handle($transaction))
        ->toThrow(SharePoolingAssetHandlerException::class, SharePoolingAssetHandlerException::noSharePoolingAsset($transaction)->getMessage());
});

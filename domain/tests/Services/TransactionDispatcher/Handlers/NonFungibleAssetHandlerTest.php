<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Services\ActionRunner\ActionRunner;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\NonFungibleAssetHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\NonFungibleAssetHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;

beforeEach(function () {
    $this->runner = Mockery::spy(ActionRunner::class);
    $this->nonFungibleAssetHandler = new NonFungibleAssetHandler($this->runner);
});

it('can handle a receive operation', function () {
    /** @var Transaction */
    $transaction = Acquisition::factory()->nonFungibleAsset()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (AcquireNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a receive operation with a fee', function () {
    /** @var Transaction */
    $transaction = Acquisition::factory()
        ->nonFungibleAsset()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (AcquireNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();
});

it('can handle a send operation', function () {
    /** @var Transaction */
    $transaction = Disposal::factory()->nonFungibleAsset()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a send operation with a fee', function () {
    /** @var Transaction */
    $transaction = Disposal::factory()
        ->nonFungibleAsset()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();
});

it('can handle a swap operation where the received asset is a non-fungible asset', function () {
    /** @var Transaction */
    $transaction = Swap::factory()->toNonFungibleAsset()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (AcquireNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where the sent asset is a non-fungible asset', function () {
    /** @var Transaction */
    $transaction = Swap::factory()->fromNonFungibleAsset()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation where both assets are non-fungible assets', function () {
    /** @var Transaction */
    $transaction = Swap::factory()->nonFungibleAssets()->make([
        'date' => LocalDate::parse('2015-10-21'),
        'marketValue' => FiatAmount::GBP('50'),
    ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (object $action) => $action instanceof DisposeOfNonFungibleAsset
            && $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo($transaction->marketValue),
    )->once();

    $this->runner->shouldHaveReceived(
        'run',
        fn (object $action) => $action instanceof AcquireNonFungibleAsset
            && $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo($transaction->marketValue),
    )->once();
});

it('can handle a swap operation with a fee', function () {
    /** @var Transaction */
    $transaction = Swap::factory()
        ->nonFungibleAssets()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (object $action) => $action instanceof DisposeOfNonFungibleAsset
            && $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(FiatAmount::GBP('45')),
    )->once();

    $this->runner->shouldHaveReceived(
        'run',
        fn (object $action) => $action instanceof AcquireNonFungibleAsset
            && $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(FiatAmount::GBP('55')),
    )->once();
});

it('can handle a swap operation with a fee where the received asset is a non-fungible asset and the sent asset is some fiat currency', function () {
    /** @var Transaction */
    $transaction = Swap::factory()
        ->toNonFungibleAsset()
        ->fromFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (AcquireNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->costBasis->isEqualTo(FiatAmount::GBP('60')),
    )->once();
});

it('can handle a swap operation with a fee where the sent asset is a non-fungible asset and the received asset is some fiat currency', function () {
    /** @var Transaction */
    $transaction = Swap::factory()
        ->fromNonFungibleAsset()
        ->toFiat()
        ->withFee(FiatAmount::GBP('10'))
        ->make([
            'date' => LocalDate::parse('2015-10-21'),
            'marketValue' => FiatAmount::GBP('50'),
        ]);

    $this->nonFungibleAssetHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (DisposeOfNonFungibleAsset $action) => $action->date->isEqualTo($transaction->date)
            && $action->proceeds->isEqualTo(FiatAmount::GBP('40')),
    )->once();
});

it('cannot handle a transaction because none of the assets is a non-fungible asset', function () {
    $transaction = Swap::factory()->make();

    expect(fn () => $this->nonFungibleAssetHandler->handle($transaction))
        ->toThrow(NonFungibleAssetHandlerException::class, NonFungibleAssetHandlerException::noNonFungibleAsset($transaction)->getMessage());
});

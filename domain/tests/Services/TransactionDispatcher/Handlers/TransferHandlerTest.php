<?php

use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Transfer;
use Illuminate\Contracts\Bus\Dispatcher;

beforeEach(function () {
    $this->dispatcher = Mockery::spy(Dispatcher::class);
    $this->transferHandler = new TransferHandler($this->dispatcher);
});

it('can handle a transfer operation', function () {
    $transaction = Transfer::factory()->withFee($fee = FiatAmount::GBP('10'))->make();

    $this->transferHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (UpdateNonAttributableAllowableCost $action) => $action->nonAttributableAllowableCost->isEqualTo($fee),
    )->once();
});

it('can handle a transfer operation with no fee', function () {
    $this->transferHandler->handle(Transfer::factory()->make());

    $this->dispatcher->shouldNotHaveReceived('dispatchSync');
});

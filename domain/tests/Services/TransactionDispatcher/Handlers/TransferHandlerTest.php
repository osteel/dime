<?php

use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Services\ActionRunner\ActionRunner;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Transfer;

beforeEach(function () {
    $this->runner = Mockery::spy(ActionRunner::class);
    $this->transferHandler = new TransferHandler($this->runner);
});

it('can handle a transfer operation', function () {
    $transaction = Transfer::factory()->withFee($fee = FiatAmount::GBP('10'))->make();

    $this->transferHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (UpdateNonAttributableAllowableCost $action) => $action->nonAttributableAllowableCost->isEqualTo($fee),
    )->once();
});

it('can handle a transfer operation with no fee', function () {
    $this->transferHandler->handle(Transfer::factory()->make());

    $this->runner->shouldNotHaveReceived('run');
});

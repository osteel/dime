<?php

use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Services\ActionRunner\ActionRunner;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\IncomeHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Acquisition;

beforeEach(function () {
    $this->runner = Mockery::spy(ActionRunner::class);
    $this->incomeHandler = new IncomeHandler($this->runner);
});

it('can handle an income transaction', function () {
    $transaction = Acquisition::factory()->income()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->incomeHandler->handle($transaction);

    $this->runner->shouldHaveReceived(
        'run',
        fn (UpdateIncome $action) => $action->incomeUpdate->isEqualTo($transaction->marketValue),
    )->once();
});

it('cannot handle a transaction because it is not income', function () {
    $transaction = Acquisition::factory()->make();

    expect(fn () => $this->incomeHandler->handle($transaction))
        ->toThrow(IncomeHandlerException::class, IncomeHandlerException::notIncome($transaction)->getMessage());

    $this->runner->shouldNotHaveReceived('run');
});

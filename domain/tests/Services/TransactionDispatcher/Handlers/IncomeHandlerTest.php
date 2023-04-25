<?php

use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\IncomeHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Acquisition;
use Illuminate\Contracts\Bus\Dispatcher;

beforeEach(function () {
    $this->dispatcher = Mockery::spy(Dispatcher::class);
    $this->incomeHandler = new IncomeHandler($this->dispatcher);
});

it('can handle an income transaction', function () {
    $transaction = Acquisition::factory()->income()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->incomeHandler->handle($transaction);

    $this->dispatcher->shouldHaveReceived(
        'dispatchSync',
        fn (UpdateIncome $action) => $action->income->isEqualTo($transaction->marketValue),
    )->once();
});

it('cannot handle a transaction because it is not income', function () {
    $transaction = Acquisition::factory()->make();

    expect(fn () => $this->incomeHandler->handle($transaction))
        ->toThrow(IncomeHandlerException::class, IncomeHandlerException::notIncome($transaction)->getMessage());

    $this->dispatcher->shouldNotHaveReceived('dispatchSync');
});

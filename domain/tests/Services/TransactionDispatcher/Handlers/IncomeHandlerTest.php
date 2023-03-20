<?php

use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\IncomeHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\IncomeHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transaction;

beforeEach(function () {
    $this->taxYearRepository = Mockery::mock(TaxYearRepository::class);
    $this->incomeHandler = new IncomeHandler($this->taxYearRepository);
});

it('can handle an income transaction', function () {
    $taxYear = Mockery::spy(TaxYear::class);

    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYear);

    $transaction = Transaction::factory()->income()->make(['marketValue' => FiatAmount::GBP('50')]);

    $this->incomeHandler->handle($transaction);

    $taxYear->shouldHaveReceived(
        'updateIncome',
        fn (UpdateIncome $action) => $action->income->isEqualTo($transaction->marketValue),
    )->once();
});

it('cannot handle a transaction because the operation is not receive', function () {
    $transaction = Transaction::factory()->send()->make();

    expect(fn () => $this->incomeHandler->handle($transaction))
        ->toThrow(IncomeHandlerException::class, IncomeHandlerException::operationIsNotReceive($transaction)->getMessage());
});

it('cannot handle a transaction because it is not income', function () {
    $transaction = Transaction::factory()->receive()->make();

    expect(fn () => $this->incomeHandler->handle($transaction))
        ->toThrow(IncomeHandlerException::class, IncomeHandlerException::notIncome($transaction)->getMessage());
});

<?php

use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Services\TransactionDispatcher\Handlers\TransferHandler;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Transactions\Transfer;

beforeEach(function () {
    $this->taxYearRepository = Mockery::mock(TaxYearRepository::class);
    $this->transferHandler = new TransferHandler($this->taxYearRepository);
});

it('can handle a transfer operation', function () {
    $taxYear = Mockery::spy(TaxYear::class);

    $this->taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);
    $this->taxYearRepository->shouldReceive('save')->once()->with($taxYear);

    $transaction = Transfer::factory()->withFee($fee = FiatAmount::GBP('10'))->make();

    $this->transferHandler->handle($transaction);

    $taxYear->shouldHaveReceived(
        'updateNonAttributableAllowableCost',
        fn (UpdateNonAttributableAllowableCost $action) => $action->nonAttributableAllowableCost->isEqualTo($fee),
    )->once();
});

it('can handle a transfer operation with no fee', function () {
    $this->transferHandler->handle(Transfer::factory()->make());

    $this->taxYearRepository->shouldNotHaveReceived('get');
});

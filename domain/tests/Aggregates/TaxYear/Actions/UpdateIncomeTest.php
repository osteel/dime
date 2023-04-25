<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\ValueObjects\FiatAmount;

it('can update the aggregate', function () {
    $taxYear = Mockery::mock(TaxYear::class);
    $taxYearRepository = Mockery::mock(TaxYearRepository::class);

    $updateIncome = new UpdateIncome(
        date: LocalDate::parse('2015-10-21'),
        income: FiatAmount::GBP('1'),
    );

    $taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);
    $taxYear->shouldReceive('updateIncome')->once()->with($updateIncome);
    $taxYearRepository->shouldReceive('save')->once()->with($taxYear);

    $updateIncome->handle($taxYearRepository);
});

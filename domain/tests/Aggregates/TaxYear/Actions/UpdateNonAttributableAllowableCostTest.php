<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\ValueObjects\FiatAmount;

it('can update the aggregate', function () {
    $taxYear = Mockery::mock(TaxYear::class);
    $taxYearRepository = Mockery::mock(TaxYearRepository::class);

    $updateNonAttributableAllowableCost = new UpdateNonAttributableAllowableCost(
        date: LocalDate::parse('2015-10-21'),
        nonAttributableAllowableCost: FiatAmount::GBP('1'),
    );

    $taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);
    $taxYear->shouldReceive('updateNonAttributableAllowableCost')->once()->with($updateNonAttributableAllowableCost);
    $taxYearRepository->shouldReceive('save')->once()->with($taxYear);

    $updateNonAttributableAllowableCost->handle($taxYearRepository);
});

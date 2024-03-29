<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Repositories\TaxYearRepository;
use Domain\Aggregates\TaxYear\TaxYearContract;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\ValueObjects\FiatAmount;

it('can revert a capital gain update', function () {
    $taxYear = Mockery::spy(TaxYearContract::class);
    $taxYearRepository = Mockery::mock(TaxYearRepository::class);

    $revertCapitalGainUpdate = new RevertCapitalGainUpdate(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(
            costBasis: FiatAmount::GBP('1'),
            proceeds: FiatAmount::GBP('2'),
        )
    );

    $taxYearRepository->shouldReceive('get')->once()->andReturn($taxYear);
    $taxYearRepository->shouldReceive('save')->once()->with($taxYear);

    $revertCapitalGainUpdate($taxYearRepository);

    $taxYear->shouldHaveReceived('revertCapitalGainUpdate')->once()->with($revertCapitalGainUpdate);
});

<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\ValueObjects\Exceptions\TaxYearIdException;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;

it('can create an aggregate root ID from a date', function (string $date, string $taxYear) {
    expect(TaxYearId::fromDate(LocalDate::parse($date))->toString())->toBe($taxYear);
})->with([
    ['2015-10-21', '2015-2016'],
    ['2016-04-05', '2015-2016'],
    ['2016-04-06', '2016-2017'],
]);

it('cannot create an aggregate root ID because the value is invalid', function () {
    expect(fn () => TaxYearId::fromString('foo'))
        ->toThrow(TaxYearIdException::class, TaxYearIdException::invalidTaxYear()->getMessage());
});

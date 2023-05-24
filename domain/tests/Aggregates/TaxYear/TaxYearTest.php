<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\TaxYear\Actions\RevertCapitalGainUpdate;
use Domain\Aggregates\TaxYear\Actions\UpdateCapitalGain;
use Domain\Aggregates\TaxYear\Actions\UpdateIncome;
use Domain\Aggregates\TaxYear\Actions\UpdateNonAttributableAllowableCost;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdated;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdateReverted;
use Domain\Aggregates\TaxYear\Events\IncomeUpdated;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostUpdated;
use Domain\Aggregates\TaxYear\Exceptions\TaxYearException;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Enums\FiatCurrency;
use Domain\Tests\Aggregates\TaxYear\TaxYearTestCase;
use Domain\ValueObjects\FiatAmount;

use function EventSauce\EventSourcing\PestTooling\expectToFail;
use function EventSauce\EventSourcing\PestTooling\given;
use function EventSauce\EventSourcing\PestTooling\then;
use function EventSauce\EventSourcing\PestTooling\when;

uses(TaxYearTestCase::class);

it('can update the capital gain', function (string $costBasis, string $proceeds) {
    when($updateCapitalGain = new UpdateCapitalGain(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds)),
    ));

    then(new CapitalGainUpdated(
        date: $updateCapitalGain->date,
        capitalGainUpdate: $updateCapitalGain->capitalGainUpdate,
        newCapitalGain: $updateCapitalGain->capitalGainUpdate,
    ));
})->with([
    'gain' => ['50', '150'],
    'loss' => ['150', '50'],
]);

it('cannot update the capital gain because the currencies don\'t match', function () {
    $capitalGainUpdate = new CapitalGain(FiatAmount::GBP('50'), FiatAmount::GBP('150'));

    given(new CapitalGainUpdated(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: $capitalGainUpdate,
        newCapitalGain: $capitalGainUpdate,
    ));

    when($updateCapitalGain = new UpdateCapitalGain(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(new FiatAmount('50', FiatCurrency::EUR), new FiatAmount('150', FiatCurrency::EUR)),
    ));

    expectToFail(TaxYearException::currencyMismatch(
        taxYearId: $this->aggregateRootId,
        action: $updateCapitalGain,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    ));
});

it('can revert a capital gain update', function (string $costBasis, string $proceeds) {
    $capitalGainUpdate = new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds));

    given(new CapitalGainUpdated(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: $capitalGainUpdate,
        newCapitalGain: $capitalGainUpdate,
    ));

    when($revertCapitalGainUpdate = new RevertCapitalGainUpdate(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds)),
    ));

    then(new CapitalGainUpdateReverted(
        date: $revertCapitalGainUpdate->date,
        capitalGainUpdate: $revertCapitalGainUpdate->capitalGainUpdate,
        newCapitalGain: new CapitalGain(FiatAmount::GBP(0), FiatAmount::GBP(0)),
    ));
})->with([
    'gain' => ['50', '150'],
    'loss' => ['150', '50'],
]);

it('cannot revert a capital gain update before the capital gain was updated', function () {
    when(new RevertCapitalGainUpdate(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(FiatAmount::GBP('50'), FiatAmount::GBP('150')),
    ));

    expectToFail(TaxYearException::cannotRevertCapitalGainUpdateBeforeCapitalGainIsUpdated(
        taxYearId: $this->aggregateRootId,
    ));
});

it('cannot revert a capital gain update because the currencies don\'t match', function () {
    $capitalGainUpdate = new CapitalGain(FiatAmount::GBP('50'), FiatAmount::GBP('150'));

    given(new CapitalGainUpdated(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: $capitalGainUpdate,
        newCapitalGain: $capitalGainUpdate,
    ));

    when($revertCapitalGainUpdate = new RevertCapitalGainUpdate(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(new FiatAmount('50', FiatCurrency::EUR), new FiatAmount('150', FiatCurrency::EUR)),
    ));

    expectToFail(TaxYearException::currencyMismatch(
        taxYearId: $this->aggregateRootId,
        action: $revertCapitalGainUpdate,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    ));
});

it('can update the income', function () {
    when($updateIncome = new UpdateIncome(date: LocalDate::parse('2015-10-21'), incomeUpdate: FiatAmount::GBP('100')));

    then(new IncomeUpdated(
        date: $updateIncome->date,
        incomeUpdate: $updateIncome->incomeUpdate,
        newIncome: $updateIncome->incomeUpdate,
    ));
});

it('cannot update the income because the currencies don\'t match', function () {
    given(new IncomeUpdated(
        date: LocalDate::parse('2015-10-21'),
        incomeUpdate: FiatAmount::GBP('100'),
        newIncome: FiatAmount::GBP('100'),
    ));

    when($updateIncome = new UpdateIncome(
        date: LocalDate::parse('2015-10-21'),
        incomeUpdate: new FiatAmount('100', FiatCurrency::EUR),
    ));

    expectToFail(TaxYearException::currencyMismatch(
        taxYearId: $this->aggregateRootId,
        action: $updateIncome,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    ));
});

it('can update the non-attributable allowable cost', function () {
    when($updateNonAttributableAllowableCost = new UpdateNonAttributableAllowableCost(
        date: LocalDate::parse('2015-10-21'),
        nonAttributableAllowableCostChange: FiatAmount::GBP('100'),
    ));

    then(new NonAttributableAllowableCostUpdated(
        date: $updateNonAttributableAllowableCost->date,
        nonAttributableAllowableCostChange: $updateNonAttributableAllowableCost->nonAttributableAllowableCostChange,
        newNonAttributableAllowableCost: $updateNonAttributableAllowableCost->nonAttributableAllowableCostChange,
    ));
});

it('cannot update the non-attributable allowable cost because the currencies don\'t match', function () {
    given(new NonAttributableAllowableCostUpdated(
        date: LocalDate::parse('2015-10-21'),
        nonAttributableAllowableCostChange: FiatAmount::GBP('100'),
        newNonAttributableAllowableCost: FiatAmount::GBP('100'),
    ));

    when($updateNonAttributableAllowableCost = new UpdateNonAttributableAllowableCost(
        date: LocalDate::parse('2015-10-21'),
        nonAttributableAllowableCostChange: new FiatAmount('100', FiatCurrency::EUR),
    ));

    expectToFail(TaxYearException::currencyMismatch(
        taxYearId: $this->aggregateRootId,
        action: $updateNonAttributableAllowableCost,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    ));
});

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
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase;

uses(TaxYearTestCase::class);

it('can update the capital gain', function (string $costBasis, string $proceeds) {
    $updateCapitalGain = new UpdateCapitalGain(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds)),
    );

    $capitalGainUpdated = new CapitalGainUpdated(
        date: $updateCapitalGain->date,
        capitalGainUpdate: $updateCapitalGain->capitalGainUpdate,
        newCapitalGain: $updateCapitalGain->capitalGainUpdate,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($updateCapitalGain)
        ->then($capitalGainUpdated);
})->with([
    'gain' => ['50', '150'],
    'loss' => ['150', '50'],
]);

it('cannot update the capital gain because the currencies don\'t match', function () {
    $capitalGainUpdate = new CapitalGain(FiatAmount::GBP('50'), FiatAmount::GBP('150'));

    $capitalGainUpdated = new CapitalGainUpdated(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: $capitalGainUpdate,
        newCapitalGain: $capitalGainUpdate,
    );

    $updateCapitalGain = new UpdateCapitalGain(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: new CapitalGain(new FiatAmount('50', FiatCurrency::EUR), new FiatAmount('150', FiatCurrency::EUR)),
    );

    $cannotUpdateCapitalGain = TaxYearException::currencyMismatch(
        taxYearId: $this->aggregateRootId,
        action: $updateCapitalGain,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainUpdated)
        ->when($updateCapitalGain)
        ->expectToFail($cannotUpdateCapitalGain);
});

it('can revert a capital gain update', function (string $costBasis, string $proceeds) {
    $capitalGainUpdate = new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds));

    $capitalGainUpdated = new CapitalGainUpdated(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: $capitalGainUpdate,
        newCapitalGain: $capitalGainUpdate,
    );

    $revertCapitalGainUpdate = new RevertCapitalGainUpdate(
        date: LocalDate::parse('2015-10-21'),
        capitalGain: new CapitalGain(FiatAmount::GBP($costBasis), FiatAmount::GBP($proceeds)),
    );

    $capitalGainReverted = new CapitalGainUpdateReverted(
        date: $revertCapitalGainUpdate->date,
        capitalGain: $revertCapitalGainUpdate->capitalGain,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainUpdated)
        ->when($revertCapitalGainUpdate)
        ->then($capitalGainReverted);
})->with([
    'gain' => ['50', '150'],
    'loss' => ['150', '50'],
]);

it('cannot revert a capital gain update before the capital gain was updated', function () {
    $revertCapitalGainUpdate = new RevertCapitalGainUpdate(
        date: LocalDate::parse('2015-10-21'),
        capitalGain: new CapitalGain(FiatAmount::GBP('50'), FiatAmount::GBP('150')),
    );

    $cannotRevertCapitalGain = TaxYearException::cannotRevertCapitalGainUpdateBeforeCapitalGainIsUpdated(
        taxYearId: $this->aggregateRootId,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($revertCapitalGainUpdate)
        ->expectToFail($cannotRevertCapitalGain);
});

it('cannot revert a capital gain update because the currencies don\'t match', function () {
    $capitalGainUpdate = new CapitalGain(FiatAmount::GBP('50'), FiatAmount::GBP('150'));

    $capitalGainUpdated = new CapitalGainUpdated(
        date: LocalDate::parse('2015-10-21'),
        capitalGainUpdate: $capitalGainUpdate,
        newCapitalGain: $capitalGainUpdate,
    );

    $revertCapitalGainUpdate = new RevertCapitalGainUpdate(
        date: LocalDate::parse('2015-10-21'),
        capitalGain: new CapitalGain(new FiatAmount('50', FiatCurrency::EUR), new FiatAmount('150', FiatCurrency::EUR)),
    );

    $cannotRevertCapitalGainUpdate = TaxYearException::currencyMismatch(
        taxYearId: $this->aggregateRootId,
        action: $revertCapitalGainUpdate,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($capitalGainUpdated)
        ->when($revertCapitalGainUpdate)
        ->expectToFail($cannotRevertCapitalGainUpdate);
});

it('can update the income', function () {
    $updateIncome = new UpdateIncome(
        date: LocalDate::parse('2015-10-21'),
        income: FiatAmount::GBP('100'),
    );

    $incomeUpdated = new IncomeUpdated(
        date: $updateIncome->date,
        income: $updateIncome->income,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($updateIncome)
        ->then($incomeUpdated);
});

it('cannot update the income because the currencies don\'t match', function () {
    $incomeUpdated = new IncomeUpdated(
        date: LocalDate::parse('2015-10-21'),
        income: FiatAmount::GBP('100'),
    );

    $updateIncome = new UpdateIncome(
        date: LocalDate::parse('2015-10-21'),
        income: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotUpdateIncome = TaxYearException::currencyMismatch(
        taxYearId: $this->aggregateRootId,
        action: $updateIncome,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($incomeUpdated)
        ->when($updateIncome)
        ->expectToFail($cannotUpdateIncome);
});

it('can update the non-attributable allowable cost', function () {
    $updateNonAttributableAllowableCost = new UpdateNonAttributableAllowableCost(
        date: LocalDate::parse('2015-10-21'),
        nonAttributableAllowableCost: FiatAmount::GBP('100'),
    );

    $nonAttributableAllowableCostUpdated = new NonAttributableAllowableCostUpdated(
        date: $updateNonAttributableAllowableCost->date,
        nonAttributableAllowableCost: $updateNonAttributableAllowableCost->nonAttributableAllowableCost,
    );

    /** @var AggregateRootTestCase $this */
    $this->when($updateNonAttributableAllowableCost)
        ->then($nonAttributableAllowableCostUpdated);
});

it('cannot update the non-attributable allowable cost because the currencies don\'t match', function () {
    $nonAttributableAllowableCostUpdated = new NonAttributableAllowableCostUpdated(
        date: LocalDate::parse('2015-10-21'),
        nonAttributableAllowableCost: FiatAmount::GBP('100'),
    );

    $updateNonAttributableAllowableCost = new UpdateNonAttributableAllowableCost(
        date: LocalDate::parse('2015-10-21'),
        nonAttributableAllowableCost: new FiatAmount('100', FiatCurrency::EUR),
    );

    $cannotUpdateNonAttributableAllowableCost = TaxYearException::currencyMismatch(
        taxYearId: $this->aggregateRootId,
        action: $updateNonAttributableAllowableCost,
        current: FiatCurrency::GBP,
        incoming: FiatCurrency::EUR,
    );

    /** @var AggregateRootTestCase $this */
    $this->given($nonAttributableAllowableCostUpdated)
        ->when($updateNonAttributableAllowableCost)
        ->expectToFail($cannotUpdateNonAttributableAllowableCost);
});

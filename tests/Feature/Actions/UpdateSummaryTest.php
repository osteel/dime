<?php

use Domain\Actions\UpdateSummary;
use Domain\Enums\FiatCurrency;
use Domain\Projections\Summary;
use Domain\ValueObjects\Exceptions\FiatAmountException;
use Domain\ValueObjects\FiatAmount;

it('can create a summary', function (string $balanceUpdate) {
    $this->assertDatabaseCount('summaries', 0);

    $updateSummary = new UpdateSummary($fiatBalance = FiatAmount::GBP($balanceUpdate));

    $updateSummary();

    $this->assertDatabaseCount('summaries', 1);

    $this->assertDatabaseHas('summaries', [
        'currency' => $fiatBalance->currency->value,
        'fiat_balance' => $fiatBalance->quantity,
    ]);
})->with([
    'positive' => '10',
    'negative' => '-10',
]);

it('can update a summary', function (string $balanceUpdate, string $result) {
    Summary::create(['currency' => FiatCurrency::GBP, 'fiat_balance' => FiatAmount::GBP('5')]);

    $this->assertDatabaseCount('summaries', 1);

    $updateSummary = new UpdateSummary($fiatBalance = FiatAmount::GBP($balanceUpdate));

    $updateSummary();

    $this->assertDatabaseCount('summaries', 1);

    $this->assertDatabaseHas('summaries', [
        'currency' => $fiatBalance->currency->value,
        'fiat_balance' => $result,
    ]);
})->with([
    'positive' => ['10', '15'],
    'negative' => ['-10', '-5'],
]);

it('cannot update a summary because the currencies don\'t match', function () {
    Summary::create(['currency' => FiatCurrency::GBP, 'fiat_balance' => FiatAmount::GBP('5')]);

    $updateSummary = new UpdateSummary(new FiatAmount('10', FiatCurrency::EUR));

    expect(fn () => $updateSummary())->toThrow(
        FiatAmountException::class,
        FiatAmountException::fiatCurrenciesDoNotMatch(FiatCurrency::GBP->value, FiatCurrency::EUR->value)->getMessage(),
    );
});

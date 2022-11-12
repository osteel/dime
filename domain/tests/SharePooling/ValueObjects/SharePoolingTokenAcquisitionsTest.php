<?php

use Domain\Enums\FiatCurrency;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingTokenAcquisitions;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('can return the average cost basis per unit of a collection of acquisitions', function (array $costBases, string $average) {
    $transactions = [];

    foreach ($costBases as $quantity => $costBasis) {
        $transactions[] = SharePoolingTokenAcquisition::factory()->make([
            'quantity' => new Quantity($quantity),
            'costBasis' => new FiatAmount($costBasis, FiatCurrency::GBP),
        ]);
    }

    $sharePoolingTransactions = SharePoolingTokenAcquisitions::make(...$transactions);

    expect($sharePoolingTransactions->averageCostBasisPerUnit())
        ->toBeInstanceOf(FiatAmount::class)
        ->toEqual(new FiatAmount($average, FiatCurrency::GBP));
})->with([
    'scenario 1' => [['10' => '10'], '1'],
    'scenario 2' => [['10' => '4', '20' => '10', '20' => '11'], '0.5'],
    'scenario 3' => [['35' => '1.12345678', '65' => '1.123456789'], '0.02246913569'],
]);

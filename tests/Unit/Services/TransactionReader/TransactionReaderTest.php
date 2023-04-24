<?php

use App\Services\TransactionReader\Exceptions\TransactionReaderException;
use App\Services\TransactionReader\TransactionReader;

beforeEach(function () {
    /** @var TransactionReader */
    $this->transactionReader = resolve(TransactionReader::class);
});

it('cannot read a spreadsheet of transactions because some headers are missing', function () {
    $path = base_path('tests/stubs/transactions/invalid_headers.csv');

    expect(fn () => $this->transactionReader->read($path)->current())
        ->toThrow(TransactionReaderException::class, TransactionReaderException::missingHeaders([
            'market_value',
            'sent_asset_is_non_fungible',
            'received_asset_is_non_fungible',
            'fee_market_value',
        ])->getMessage());
});

it('can read a spreadsheet of transactions', function () {
    $path = base_path('tests/stubs/transactions/valid.csv');

    /** @var Generator */
    $transactions = $this->transactionReader->read($path);

    expect(iterator_count($transactions))->toBe(17);

    /** @var Generator */
    $transactions = $this->transactionReader->read($path);

    expect($transactions->current())->toBe([
        'date' => '08/01/2020',
        'operation' => 'swap',
        'market_value' => '9900',
        'sent_asset' => 'GBP',
        'sent_quantity' => '10000',
        'sent_asset_is_non_fungible' => '',
        'received_asset' => 'BTC',
        'received_quantity' => '0.99',
        'received_asset_is_non_fungible' => '',
        'fee_currency' => 'GBP',
        'fee_quantity' => '100',
        'fee_market_value' => '100',
        'income' => '',
    ]);
});

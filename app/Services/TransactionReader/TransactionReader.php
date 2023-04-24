<?php

declare(strict_types=1);

namespace App\Services\TransactionReader;

use App\Services\TransactionReader\Exceptions\TransactionReaderException;
use Generator;

interface TransactionReader
{
    public const REQUIRED_HEADERS = [
        'date',
        'operation',
        'market_value',
        'sent_asset',
        'sent_quantity',
        'sent_asset_is_non_fungible',
        'received_asset',
        'received_quantity',
        'received_asset_is_non_fungible',
        'fee_currency',
        'fee_quantity',
        'fee_market_value',
        'income',
    ];

    /**
     * @return Generator<array{date:string,operation:string,market_value:string,sent_asset:string,sent_quantity:string,sent_asset_is_non_fungible:string,received_asset:string,received_quantity:string,received_asset_is_non_fungible:string,fee_currency:string,fee_quantity:string,fee_market_value:string,income:string}>
     *
     * @throws TransactionReaderException
     */
    public function read(string $path): Generator;
}

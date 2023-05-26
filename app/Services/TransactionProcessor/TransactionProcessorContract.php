<?php

declare(strict_types=1);

namespace App\Services\TransactionProcessor;

interface TransactionProcessorContract
{
    /** @param array{date:string,operation:string,market_value:string,sent_asset:string,sent_quantity:string,sent_asset_is_non_fungible:string,received_asset:string,received_quantity:string,received_asset_is_non_fungible:string,fee_currency:string,fee_quantity:string,fee_market_value:string,income:string} $rawTransaction */
    public function process(array $rawTransaction): void;
}

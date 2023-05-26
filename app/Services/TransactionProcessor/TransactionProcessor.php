<?php

declare(strict_types=1);

namespace App\Services\TransactionProcessor;

use App\Services\TransactionProcessor\Exceptions\TransactionProcessorException;
use Brick\DateTime\LocalDate;
use DateTime;
use Domain\Enums\FiatCurrency;
use Domain\Enums\Operation;
use Domain\Services\TransactionDispatcher\TransactionDispatcherContract;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;
use Domain\ValueObjects\Transactions\Transfer;

final class TransactionProcessor implements TransactionProcessorContract
{
    public function __construct(private readonly TransactionDispatcherContract $transactionDispatcher)
    {
    }

    /** @param array{date:string,operation:string,market_value:string,sent_asset:string,sent_quantity:string,sent_asset_is_non_fungible:string,received_asset:string,received_quantity:string,received_asset_is_non_fungible:string,fee_currency:string,fee_quantity:string,fee_market_value:string,income:string} $rawTransaction */
    public function process(array $rawTransaction): void
    {
        $transaction = match ($this->toOperation($rawTransaction['operation'])) {
            Operation::Receive => $this->processReceive($rawTransaction),
            Operation::Send => $this->processSend($rawTransaction),
            Operation::Swap => $this->processSwap($rawTransaction),
            Operation::Transfer => $this->processTransfer($rawTransaction),
        };

        $this->transactionDispatcher->dispatch($transaction);
    }

    /** @param array{date:string,operation:string,market_value:string,sent_asset:string,sent_quantity:string,sent_asset_is_non_fungible:string,received_asset:string,received_quantity:string,received_asset_is_non_fungible:string,fee_currency:string,fee_quantity:string,fee_market_value:string,income:string} $rawTransaction */
    private function processReceive(array $rawTransaction): Acquisition
    {
        return new Acquisition(
            date: $this->toDate($rawTransaction['date']),
            asset: $this->toAsset($rawTransaction['received_asset'], $rawTransaction['received_asset_is_non_fungible']),
            quantity: $this->toQuantity($rawTransaction['received_quantity']),
            marketValue: $this->toFiatAmount($rawTransaction['market_value']),
            fee: $this->toFee($rawTransaction['fee_currency'], $rawTransaction['fee_quantity'], $rawTransaction['fee_market_value']),
            isIncome: $this->toBoolean($rawTransaction['income']),
        );
    }

    /** @param array{date:string,operation:string,market_value:string,sent_asset:string,sent_quantity:string,sent_asset_is_non_fungible:string,received_asset:string,received_quantity:string,received_asset_is_non_fungible:string,fee_currency:string,fee_quantity:string,fee_market_value:string,income:string} $rawTransaction */
    private function processSend(array $rawTransaction): Disposal
    {
        return new Disposal(
            date: $this->toDate($rawTransaction['date']),
            asset: $this->toAsset($rawTransaction['sent_asset'], $rawTransaction['sent_asset_is_non_fungible']),
            quantity: $this->toQuantity($rawTransaction['sent_quantity']),
            marketValue: $this->toFiatAmount($rawTransaction['market_value']),
            fee: $this->toFee($rawTransaction['fee_currency'], $rawTransaction['fee_quantity'], $rawTransaction['fee_market_value']),
        );
    }

    /** @param array{date:string,operation:string,market_value:string,sent_asset:string,sent_quantity:string,sent_asset_is_non_fungible:string,received_asset:string,received_quantity:string,received_asset_is_non_fungible:string,fee_currency:string,fee_quantity:string,fee_market_value:string,income:string} $rawTransaction */
    private function processSwap(array $rawTransaction): Swap
    {
        return new Swap(
            date: $this->toDate($rawTransaction['date']),
            disposedOfAsset: $this->toAsset($rawTransaction['sent_asset'], $rawTransaction['sent_asset_is_non_fungible']),
            disposedOfQuantity: $this->toQuantity($rawTransaction['sent_quantity']),
            acquiredAsset: $this->toAsset($rawTransaction['received_asset'], $rawTransaction['received_asset_is_non_fungible']),
            acquiredQuantity: $this->toQuantity($rawTransaction['received_quantity']),
            marketValue: $this->toFiatAmount($rawTransaction['market_value']),
            fee: $this->toFee($rawTransaction['fee_currency'], $rawTransaction['fee_quantity'], $rawTransaction['fee_market_value']),
        );
    }

    /** @param array{date:string,operation:string,market_value:string,sent_asset:string,sent_quantity:string,sent_asset_is_non_fungible:string,received_asset:string,received_quantity:string,received_asset_is_non_fungible:string,fee_currency:string,fee_quantity:string,fee_market_value:string,income:string} $rawTransaction */
    private function processTransfer(array $rawTransaction): Transfer
    {
        return new Transfer(
            date: $this->toDate($rawTransaction['date']),
            asset: $this->toAsset($rawTransaction['sent_asset'], $rawTransaction['sent_asset_is_non_fungible']),
            quantity: $this->toQuantity($rawTransaction['sent_quantity']),
            fee: $this->toFee($rawTransaction['fee_currency'], $rawTransaction['fee_quantity'], $rawTransaction['fee_market_value']),
        );
    }

    private function toOperation(string $value): Operation
    {
        return Operation::from($value);
    }

    /** @throws TransactionProcessorException */
    private function toFiatAmount(string $value): FiatAmount
    {
        return strlen($value) === 0
            ? throw TransactionProcessorException::invalidAmount($value)
            : new FiatAmount($value, FiatCurrency::GBP);
    }

    /** @throws TransactionProcessorException */
    private function toDate(string $value): LocalDate
    {
        $date = DateTime::createFromFormat('d/m/Y', $value);

        if ($date === false) {
            throw TransactionProcessorException::cannotParseDate($value);
        }

        return LocalDate::parse($date->format('Y-m-d'));
    }

    private function toBoolean(string $value): bool
    {
        $value = strtolower(trim($value));

        return in_array($value, ['true', 'yes', 'y', '1']);
    }

    /** @throws TransactionProcessorException */
    private function toAsset(string $symbol, string $isNonFungibleAsset = ''): Asset
    {
        return empty($symbol)
            ? throw TransactionProcessorException::invalidAsset($symbol)
            : new Asset($symbol, $this->toBoolean($isNonFungibleAsset));
    }

    private function toFee(string $symbol, string $quantity, string $marketValue): ?Fee
    {
        return empty($symbol) ? null : new Fee(
            currency: $this->toAsset($symbol),
            quantity: $this->toQuantity($quantity),
            marketValue: $this->toFiatAmount($marketValue),
        );
    }

    private function toQuantity(string $value): Quantity
    {
        return new Quantity(empty($value) ? '0' : $value);
    }
}

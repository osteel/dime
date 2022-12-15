<?php

declare(strict_types=1);

namespace App\Services\TransactionProcessor;

use App\Services\TransactionProcessor\Exceptions\TransactionProcessorException;
use Brick\DateTime\LocalDate;
use DateTime;
use Domain\Enums\FiatCurrency;
use Domain\Enums\Operation;
use Domain\Services\TransactionDispatcher\TransactionDispatcher;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transaction;

class TransactionProcessor
{
    public function __construct(private readonly TransactionDispatcher $transactionDispatcher)
    {
    }

    /** @param array<string, string> $rawTransaction */
    public function process(array $rawTransaction): void
    {
        match ($this->toOperation($rawTransaction['Operation'])) {
            Operation::Receive => $this->processReceive($rawTransaction),
            Operation::Send => $this->processSend($rawTransaction),
            Operation::Swap => $this->processSwap($rawTransaction),
            Operation::Transfer => $this->processTransfer($rawTransaction),
        };
    }

    /** @param array<string, string> $rawTransaction */
    private function processReceive(array $rawTransaction): void
    {
        $transaction = new Transaction(
            date: $this->toDate($rawTransaction['Date']),
            operation: Operation::Receive,
            marketValue: $this->toFiatAmount($rawTransaction['Market value']),
            isIncome: $this->toBoolean($rawTransaction['Income']),
            receivedAsset: $this->toAsset($rawTransaction['Received asset']),
            receivedQuantity: $this->toQuantity($rawTransaction['Received quantity']),
            receivedAssetIsNft: $this->toBoolean($rawTransaction['Received asset is NFT']),
            networkFeeCurrency: $this->toAsset($rawTransaction['Network fee currency']),
            networkFeeQuantity: $this->toQuantity($rawTransaction['Network fee quantity']),
            networkFeeMarketValue: $this->toFiatAmount($rawTransaction['Network fee market value']),
            platformFeeCurrency: $this->toAsset($rawTransaction['Platform fee currency']),
            platformFeeQuantity: $this->toQuantity($rawTransaction['Platform fee quantity']),
            platformFeeMarketValue: $this->toFiatAmount($rawTransaction['Platform fee market value']),
        );

        $this->transactionDispatcher->dispatch($transaction);
    }

    /** @param array<string, string> $rawTransaction */
    private function processSend(array $rawTransaction): void
    {
        $transaction = new Transaction(
            date: $this->toDate($rawTransaction['Date']),
            operation: Operation::Send,
            marketValue: $this->toFiatAmount($rawTransaction['Market value']),
            isIncome: $this->toBoolean($rawTransaction['Income']),
            sentAsset: $this->toAsset($rawTransaction['Sent asset']),
            sentQuantity: $this->toQuantity($rawTransaction['Sent quantity']),
            sentAssetIsNft: $this->toBoolean($rawTransaction['Sent asset is NFT']),
            networkFeeCurrency: $this->toAsset($rawTransaction['Network fee currency']),
            networkFeeQuantity: $this->toQuantity($rawTransaction['Network fee quantity']),
            networkFeeMarketValue: $this->toFiatAmount($rawTransaction['Network fee market value']),
            platformFeeCurrency: $this->toAsset($rawTransaction['Platform fee currency']),
            platformFeeQuantity: $this->toQuantity($rawTransaction['Platform fee quantity']),
            platformFeeMarketValue: $this->toFiatAmount($rawTransaction['Platform fee market value']),
        );

        $this->transactionDispatcher->dispatch($transaction);
    }

    /** @param array<string, string> $rawTransaction */
    private function processSwap(array $rawTransaction): void
    {
        $transaction = new Transaction(
            date: $this->toDate($rawTransaction['Date']),
            operation: Operation::Swap,
            marketValue: $this->toFiatAmount($rawTransaction['Market value']),
            isIncome: $this->toBoolean($rawTransaction['Income']),
            sentAsset: $this->toAsset($rawTransaction['Sent asset']),
            sentQuantity: $this->toQuantity($rawTransaction['Sent quantity']),
            sentAssetIsNft: $this->toBoolean($rawTransaction['Sent asset is NFT']),
            receivedAsset: $this->toAsset($rawTransaction['Received asset']),
            receivedQuantity: $this->toQuantity($rawTransaction['Received quantity']),
            receivedAssetIsNft: $this->toBoolean($rawTransaction['Received asset is NFT']),
            networkFeeCurrency: $this->toAsset($rawTransaction['Network fee currency']),
            networkFeeQuantity: $this->toQuantity($rawTransaction['Network fee quantity']),
            networkFeeMarketValue: $this->toFiatAmount($rawTransaction['Network fee market value']),
            platformFeeCurrency: $this->toAsset($rawTransaction['Platform fee currency']),
            platformFeeQuantity: $this->toQuantity($rawTransaction['Platform fee quantity']),
            platformFeeMarketValue: $this->toFiatAmount($rawTransaction['Platform fee market value']),
        );

        $this->transactionDispatcher->dispatch($transaction);
    }

    /** @param array<string, string> $rawTransaction */
    private function processTransfer(array $rawTransaction): void
    {
        $transaction = new Transaction(
            date: $this->toDate($rawTransaction['Date']),
            operation: Operation::Transfer,
            marketValue: $this->toFiatAmount($rawTransaction['Market value']),
            isIncome: $this->toBoolean($rawTransaction['Income']),
            sentAsset: $this->toAsset($rawTransaction['Sent asset']),
            sentQuantity: $this->toQuantity($rawTransaction['Sent quantity']),
            sentAssetIsNft: $this->toBoolean($rawTransaction['Sent asset is NFT']),
            networkFeeCurrency: $this->toAsset($rawTransaction['Network fee currency']),
            networkFeeQuantity: $this->toQuantity($rawTransaction['Network fee quantity']),
            networkFeeMarketValue: $this->toFiatAmount($rawTransaction['Network fee market value']),
            platformFeeCurrency: $this->toAsset($rawTransaction['Platform fee currency']),
            platformFeeQuantity: $this->toQuantity($rawTransaction['Platform fee quantity']),
            platformFeeMarketValue: $this->toFiatAmount($rawTransaction['Platform fee market value']),
        );

        $this->transactionDispatcher->dispatch($transaction);
    }

    private function toOperation(string $value): Operation
    {
        return Operation::from($value);
    }

    private function toFiatAmount(string $value): ?FiatAmount
    {
        return empty($value) ? null : new FiatAmount($value, FiatCurrency::GBP);
    }

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

        if (in_array($value, ['true', 'yes', 'y', '1'])) {
            return true;
        }

        return false;
    }

    private function toAsset(string $value): ?string
    {
        return empty($value) ? null : strtoupper(trim($value));
    }

    private function toQuantity(string $value): Quantity
    {
        return new Quantity(empty($value) ? '0' : $value);
    }
}

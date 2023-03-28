<?php

declare(strict_types=1);

namespace App\Services\TransactionProcessor;

use App\Services\TransactionProcessor\Exceptions\TransactionProcessorException;
use Brick\DateTime\LocalDate;
use DateTime;
use Domain\Enums\FiatCurrency;
use Domain\Enums\Operation;
use Domain\Services\TransactionDispatcher\TransactionDispatcher;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Fee;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;
use Domain\ValueObjects\Transactions\Transfer;

class TransactionProcessor
{
    public function __construct(private readonly TransactionDispatcher $transactionDispatcher)
    {
    }

    /** @param array<string,string> $rawTransaction */
    public function process(array $rawTransaction): void
    {
        $transaction = match ($this->toOperation($rawTransaction['Operation'])) {
            Operation::Receive => $this->processReceive($rawTransaction),
            Operation::Send => $this->processSend($rawTransaction),
            Operation::Swap => $this->processSwap($rawTransaction),
            Operation::Transfer => $this->processTransfer($rawTransaction),
        };

        $this->transactionDispatcher->dispatch($transaction);
    }

    /** @param array<string,string> $rawTransaction */
    private function processReceive(array $rawTransaction): Acquisition
    {
        return new Acquisition(
            date: $this->toDate($rawTransaction['Date']),
            asset: $this->toAsset($rawTransaction['Received asset'], $rawTransaction['Received asset is non-fungible']),
            quantity: $this->toQuantity($rawTransaction['Received quantity']),
            marketValue: $this->toFiatAmount($rawTransaction['Market value']),
            fee: $this->toFee($rawTransaction['Fee currency'], $rawTransaction['Fee quantity'], $rawTransaction['Fee market value']),
            isIncome: $this->toBoolean($rawTransaction['Income']),
        );
    }

    /** @param array<string,string> $rawTransaction */
    private function processSend(array $rawTransaction): Disposal
    {
        return new Disposal(
            date: $this->toDate($rawTransaction['Date']),
            asset: $this->toAsset($rawTransaction['Sent asset'], $rawTransaction['Sent asset is non-fungible']),
            quantity: $this->toQuantity($rawTransaction['Sent quantity']),
            marketValue: $this->toFiatAmount($rawTransaction['Market value']),
            fee: $this->toFee($rawTransaction['Fee currency'], $rawTransaction['Fee quantity'], $rawTransaction['Fee market value']),
        );
    }

    /** @param array<string,string> $rawTransaction */
    private function processSwap(array $rawTransaction): Swap
    {
        return new Swap(
            date: $this->toDate($rawTransaction['Date']),
            disposedOfAsset: $this->toAsset($rawTransaction['Sent asset'], $rawTransaction['Sent asset is non-fungible']),
            disposedOfQuantity: $this->toQuantity($rawTransaction['Sent quantity']),
            acquiredAsset: $this->toAsset($rawTransaction['Received asset'], $rawTransaction['Received asset is non-fungible']),
            acquiredQuantity: $this->toQuantity($rawTransaction['Received quantity']),
            marketValue: $this->toFiatAmount($rawTransaction['Market value']),
            fee: $this->toFee($rawTransaction['Fee currency'], $rawTransaction['Fee quantity'], $rawTransaction['Fee market value']),
        );
    }

    /** @param array<string,string> $rawTransaction */
    private function processTransfer(array $rawTransaction): Transfer
    {
        return new Transfer(
            date: $this->toDate($rawTransaction['Date']),
            asset: $this->toAsset($rawTransaction['Sent asset'], $rawTransaction['Sent asset is non-fungible']),
            quantity: $this->toQuantity($rawTransaction['Sent quantity']),
            fee: $this->toFee($rawTransaction['Fee currency'], $rawTransaction['Fee quantity'], $rawTransaction['Fee market value']),
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

        if (in_array($value, ['true', 'yes', 'y', '1'])) {
            return true;
        }

        return false;
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

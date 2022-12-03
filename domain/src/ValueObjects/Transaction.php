<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Enums\Operation;
use Domain\Tests\Factories\ValueObjects\TransactionFactory;
use Domain\ValueObjects\Exceptions\TransactionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stringable;

final class Transaction implements Stringable
{
    use HasFactory;

    /** @throws TransactionException */
    public function __construct(
        public readonly LocalDate $date,
        public readonly Operation $operation,
        public readonly bool $isIncome,
        public readonly FiatAmount $costBasis,
        public readonly ?string $sentAsset,
        public readonly Quantity $sentQuantity,
        public readonly bool $sentAssetIsNft,
        public readonly ?string $receivedAsset,
        public readonly Quantity $receivedQuantity,
        public readonly bool $receivedAssetIsNft,
        public readonly ?string $transactionFeeCurrency,
        public readonly Quantity $transactionFeeQuantity,
        public readonly ?FiatAmount $transactionFeeCostBasis,
        public readonly ?string $exchangeFeeCurrency,
        public readonly Quantity $exchangeFeeQuantity,
        public readonly ?FiatAmount $exchangeFeeCostBasis,
    ) {
        match ($operation) {
            Operation::Receive => $this->validateReceive($sentAsset, $receivedAsset, $receivedQuantity),
            Operation::Send => $this->validateSend($sentAsset, $sentQuantity, $receivedAsset),
            Operation::Swap => $this->validateSwap($sentAsset, $sentQuantity, $receivedAsset, $receivedQuantity),
            Operation::Transfer => $this->validateTransfer($sentAsset, $sentQuantity, $receivedAsset),
        };
    }

    /** @return TransactionFactory<static> */
    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }

    public function isReceive(): bool
    {
        return $this->operation === Operation::Receive;
    }

    public function isSend(): bool
    {
        return $this->operation === Operation::Send;
    }

    public function isSwap(): bool
    {
        return $this->operation === Operation::Swap;
    }

    public function isTransfer(): bool
    {
        return $this->operation === Operation::Transfer;
    }

    /** @throws TransactionException */
    private function validateReceive(?string $sentAsset, ?string $receivedAsset, Quantity $receivedQuantity): void
    {
        $error = 'Receive operations should %s';

        $this->notHaveASentAsset($sentAsset, $error);
        $this->haveAReceivedAsset($receivedAsset, $error);
        $this->haveAReceivedQuantity($receivedQuantity, $error);
    }

    /** @throws TransactionException */
    private function validateSend(?string $sentAsset, Quantity $sentQuantity, ?string $receivedAsset): void
    {
        $error = 'Send operations should %s';

        $this->haveASentAsset($sentAsset, $error);
        $this->haveASentQuantity($sentQuantity, $error);
        $this->notHaveAReceivedAsset($receivedAsset, $error);
    }

    /** @throws TransactionException */
    private function validateSwap(
        ?string $sentAsset,
        Quantity $sentQuantity,
        ?string $receivedAsset,
        Quantity $receivedQuantity,
    ): void {
        $error = 'Swap operations should %s';

        $this->haveASentAsset($sentAsset, $error);
        $this->haveASentQuantity($sentQuantity, $error);
        $this->haveAReceivedAsset($receivedAsset, $error);
        $this->haveAReceivedQuantity($receivedQuantity, $error);
    }

    /** @throws TransactionException */
    private function validateTransfer(?string $sentAsset, Quantity $sentQuantity, ?string $receivedAsset): void
    {
        $error = 'Transfer operations should %s';

        $this->haveASentAsset($sentAsset, $error);
        $this->haveASentQuantity($sentQuantity, $error);
        $this->notHaveAReceivedAsset($receivedAsset, $error);
    }

    /** @throws TransactionException */
    private function haveASentAsset(?string $asset, string $error): void
    {
        ! is_null($asset) || throw TransactionException::invalidData(sprintf($error, 'have a sent asset'), $this);
    }

    /** @throws TransactionException */
    private function notHaveASentAsset(?string $asset, string $error): void
    {
        is_null($asset) || throw TransactionException::invalidData(sprintf($error, 'not have a sent asset'), $this);
    }

    /** @throws TransactionException */
    private function haveAReceivedAsset(?string $asset, string $error): void
    {
        ! is_null($asset) || throw TransactionException::invalidData(sprintf($error, 'have a received asset'), $this);
    }

    /** @throws TransactionException */
    private function notHaveAReceivedAsset(?string $asset, string $error): void
    {
        is_null($asset) || throw TransactionException::invalidData(sprintf($error, 'not have a received asset'), $this);
    }

    /** @throws TransactionException */
    private function haveAReceivedQuantity(Quantity $quantity, string $error): void
    {
        $quantity->isGreaterThan('0') || throw TransactionException::invalidData(
            sprintf($error, 'have a received quantity greater than zero'),
            $this,
        );
    }

    /** @throws TransactionException */
    private function haveASentQuantity(Quantity $quantity, string $error): void
    {
        $quantity->isGreaterThan('0') || throw TransactionException::invalidData(
            sprintf($error, 'have a sent quantity greater than zero'),
            $this,
        );
    }

    public function __toString(): string
    {
        return sprintf(
            '%s | %s | income: %s | cost basis: %s | sent: %s | quantity: %s | NFT: %s | received: %s | quantity: %s | NFT: %s | Tx fee: %s | quantity: %s | Cex fee: %s | quantity: %s',
            $this->date->__toString(),
            $this->operation->value,
            $this->isIncome ? 'yes' : 'no',
            $this->costBasis->__toString(),
            $this->sentAsset,
            $this->sentQuantity->__toString(),
            $this->sentAssetIsNft ? 'yes' : 'no',
            $this->receivedAsset,
            $this->receivedQuantity->__toString(),
            $this->receivedAssetIsNft ? 'yes' : 'no',
            $this->transactionFeeCurrency,
            $this->transactionFeeQuantity->__toString(),
            $this->exchangeFeeCurrency,
            $this->exchangeFeeQuantity->__toString(),
        );
    }
}

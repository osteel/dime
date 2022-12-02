<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Enums\Operation;
use Domain\Tests\Factories\ValueObjects\TransactionFactory;
use Domain\ValueObjects\Exceptions\TransactionException;
use Domain\ValueObjects\FiatAmount;
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
        public readonly ?string $exchangeFeeCurrency,
        public readonly Quantity $exchangeFeeQuantity,
    ) {
        match ($operation) {
            Operation::Receive => $this->validateReceive($sentAsset, $receivedAsset, $receivedQuantity),
        };
    }

    /** @return TransactionFactory<static> */
    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }

    /** @throws TransactionException */
    private function validateReceive(?string $sentAsset, ?string $receivedAsset, Quantity $receivedQuantity): void
    {
        $error = 'Receive operations should %s';

        if (! is_null($sentAsset)) {
            throw TransactionException::invalidData(sprintf($error, 'not have a sent asset'), $this);
        }

        if (is_null($receivedAsset)) {
            throw TransactionException::invalidData(sprintf($error, 'have a received asset'), $this);
        }

        if ($receivedQuantity->isLessThanOrEqualTo('0')) {
            throw TransactionException::invalidData(sprintf($error, 'have a received quantity greater than zero'), $this);
        }
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

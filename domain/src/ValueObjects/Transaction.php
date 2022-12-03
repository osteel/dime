<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePooling\Services\AssetSymbolNormaliser\AssetSymbolNormaliser;
use Domain\Enums\Operation;
use Domain\Tests\Factories\ValueObjects\TransactionFactory;
use Domain\ValueObjects\Exceptions\TransactionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stringable;

final class Transaction implements Stringable
{
    use HasFactory;

    public readonly ?string $sentAsset;
    public readonly Quantity $sentQuantity;
    public readonly ?string $receivedAsset;
    public readonly Quantity $receivedQuantity;
    public readonly Quantity $transactionFeeQuantity;
    public readonly Quantity $exchangeFeeQuantity;

    /** @throws TransactionException */
    public function __construct(
        public readonly LocalDate $date,
        public readonly Operation $operation,
        public readonly FiatAmount $costBasis,
        public readonly bool $isIncome = false,
        ?string $sentAsset = null,
        ?Quantity $sentQuantity = null,
        public readonly bool $sentAssetIsNft = false,
        ?string $receivedAsset = null,
        ?Quantity $receivedQuantity = null,
        public readonly bool $receivedAssetIsNft = false,
        public readonly ?string $transactionFeeCurrency = null,
        ?Quantity $transactionFeeQuantity = null,
        public readonly ?FiatAmount $transactionFeeCostBasis = null,
        public readonly ?string $exchangeFeeCurrency = null,
        ?Quantity $exchangeFeeQuantity = null,
        public readonly ?FiatAmount $exchangeFeeCostBasis = null,
    ) {
        $this->sentAsset = $sentAssetIsNft ? $sentAsset : AssetSymbolNormaliser::normalise($sentAsset);
        $this->sentQuantity = $sentQuantity ?? Quantity::zero();
        $this->receivedAsset = $receivedAssetIsNft ? $receivedAsset : AssetSymbolNormaliser::normalise($receivedAsset);
        $this->receivedQuantity = $receivedQuantity ?? Quantity::zero();
        $this->transactionFeeQuantity = $transactionFeeQuantity ?? Quantity::zero();
        $this->exchangeFeeQuantity = $exchangeFeeQuantity ?? Quantity::zero();

        match ($operation) {
            Operation::Receive => $this->validateReceive(),
            Operation::Send => $this->validateSend(),
            Operation::Swap => $this->validateSwap(),
            Operation::Transfer => $this->validateTransfer(),
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

    public function involvesNfts(): bool
    {
        return $this->sentAssetIsNft || $this->receivedAssetIsNft;
    }

    public function involvesSharePooling(): bool
    {
        return ! $this->isTransfer() && (! $this->sentAssetIsNft || ! $this->receivedAssetIsNft);
    }

    /** @throws TransactionException */
    private function validateReceive(): void
    {
        $error = 'Receive operations should %s';

        $this->notHaveASentAsset($error)->haveAReceivedAsset($error)->haveAReceivedQuantity($error);
    }

    /** @throws TransactionException */
    private function validateSend(): void
    {
        $error = 'Send operations should %s';

        $this->haveASentAsset($error)->haveASentQuantity($error)->notHaveAReceivedAsset($error);
    }

    /** @throws TransactionException */
    private function validateSwap(): void
    {
        $error = 'Swap operations should %s';

        $this->haveASentAsset($error)
            ->haveASentQuantity($error)
            ->haveAReceivedAsset($error)
            ->haveAReceivedQuantity($error);

        $this->sentAsset !== $this->receivedAsset
            || throw TransactionException::invalidData(sprintf($error, 'have different assets'), $this);
    }

    /** @throws TransactionException */
    private function validateTransfer(): void
    {
        $error = 'Transfer operations should %s';

        $this->haveASentAsset($error)->haveASentQuantity($error)->notHaveAReceivedAsset($error);
    }

    /** @throws TransactionException */
    private function haveASentAsset(string $error): self
    {
        ! is_null($this->sentAsset)
            || throw TransactionException::invalidData(sprintf($error, 'have a sent asset'), $this);

        return $this;
    }

    /** @throws TransactionException */
    private function notHaveASentAsset(string $error): self
    {
        is_null($this->sentAsset)
            || throw TransactionException::invalidData(sprintf($error, 'not have a sent asset'), $this);

        return $this;
    }

    /** @throws TransactionException */
    private function haveAReceivedAsset(string $error): self
    {
        ! is_null($this->receivedAsset)
            || throw TransactionException::invalidData(sprintf($error, 'have a received asset'), $this);

        return $this;
    }

    /** @throws TransactionException */
    private function notHaveAReceivedAsset(string $error): self
    {
        is_null($this->receivedAsset)
            || throw TransactionException::invalidData(sprintf($error, 'not have a received asset'), $this);

        return $this;
    }

    /** @throws TransactionException */
    private function haveAReceivedQuantity(string $error): self
    {
        $this->receivedQuantity->isGreaterThan('0') || throw TransactionException::invalidData(
            sprintf($error, 'have a received quantity greater than zero'),
            $this,
        );

        return $this;
    }

    /** @throws TransactionException */
    private function haveASentQuantity(string $error): self
    {
        $this->sentQuantity->isGreaterThan('0') || throw TransactionException::invalidData(
            sprintf($error, 'have a sent quantity greater than zero'),
            $this,
        );

        return $this;
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

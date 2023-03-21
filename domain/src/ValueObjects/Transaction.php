<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePooling\Services\AssetSymbolNormaliser\AssetSymbolNormaliser;
use Domain\Enums\FiatCurrency;
use Domain\Enums\Operation;
use Domain\Tests\Factories\ValueObjects\TransactionFactory;
use Domain\ValueObjects\Exceptions\TransactionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stringable;

final readonly class Transaction implements Stringable
{
    use HasFactory;

    public ?string $sentAsset;
    public Quantity $sentQuantity;
    public ?string $receivedAsset;
    public Quantity $receivedQuantity;
    public Quantity $feeQuantity;

    /** @throws TransactionException */
    public function __construct(
        public LocalDate $date,
        public Operation $operation,
        public ?FiatAmount $marketValue = null,
        public bool $isIncome = false,
        ?string $sentAsset = null,
        ?Quantity $sentQuantity = null,
        public bool $sentAssetIsNft = false,
        ?string $receivedAsset = null,
        ?Quantity $receivedQuantity = null,
        public bool $receivedAssetIsNft = false,
        public ?string $feeCurrency = null,
        ?Quantity $feeQuantity = null,
        public ?FiatAmount $feeMarketValue = null,
    ) {
        $this->sentAsset = $sentAssetIsNft ? $sentAsset : AssetSymbolNormaliser::normalise($sentAsset);
        $this->sentQuantity = $sentQuantity ?? Quantity::zero();
        $this->receivedAsset = $receivedAssetIsNft ? $receivedAsset : AssetSymbolNormaliser::normalise($receivedAsset);
        $this->receivedQuantity = $receivedQuantity ?? Quantity::zero();
        $this->feeQuantity = $feeQuantity ?? Quantity::zero();

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

    public function fallsUnderSharePooling(): bool
    {
        return $this->sentAssetFallsUnderSharePooling() || $this->receivedAssetFallsUnderSharePooling();
    }

    public function sentAssetFallsUnderSharePooling(): bool
    {
        return ! is_null($this->sentAsset) && ! $this->sentAssetIsNft && ! $this->sentAssetIsFiat();
    }

    public function receivedAssetFallsUnderSharePooling(): bool
    {
        return ! is_null($this->receivedAsset) && ! $this->receivedAssetIsNft && ! $this->receivedAssetIsFiat();
    }

    public function sentAssetIsFiat(): bool
    {
        return $this->sentAsset ? FiatCurrency::tryFrom($this->sentAsset) !== null : false;
    }

    public function receivedAssetIsFiat(): bool
    {
        return $this->receivedAsset ? FiatCurrency::tryFrom($this->receivedAsset) !== null : false;
    }

    public function oneAssetIsFiat(): bool
    {
        return $this->sentAssetIsFiat() || $this->receivedAssetIsFiat();
    }

    public function hasFee(): bool
    {
        return $this->feeMarketValue?->isGreaterThan('0') ?? false;
    }

    public function feeIsFiat(): bool
    {
        return $this->feeCurrency ? FiatCurrency::tryFrom($this->feeCurrency) !== null : false;
    }

    /** @throws TransactionException */
    private function validateReceive(): void
    {
        $error = 'Receive operations should %s';

        $this->haveAMarketValue($error)
            ->notHaveASentAsset($error)
            ->haveAReceivedAsset($error)
            ->haveAReceivedQuantity($error);
    }

    /** @throws TransactionException */
    private function validateSend(): void
    {
        $error = 'Send operations should %s';

        $this->haveAMarketValue($error)
            ->haveASentAsset($error)
            ->haveASentQuantity($error)
            ->notHaveAReceivedAsset($error);
    }

    /** @throws TransactionException */
    private function validateSwap(): void
    {
        $error = 'Swap operations should %s';

        $this->haveAMarketValue($error)
            ->haveASentAsset($error)
            ->haveASentQuantity($error)
            ->haveAReceivedAsset($error);

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
    private function haveAMarketValue(string $error): self
    {
        ! is_null($this->marketValue)
            || throw TransactionException::invalidData(sprintf($error, 'have a market value'), $this);

        return $this;
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
            '%s | %s | income: %s | cost basis: %s | sent: %s | quantity: %s | NFT: %s | received: %s | quantity: %s | NFT: %s | Fee: %s | quantity: %s',
            $this->date->__toString(),
            $this->operation->value,
            $this->isIncome ? 'yes' : 'no',
            $this->marketValue?->__toString() ?? 'N/A',
            $this->sentAsset,
            $this->sentQuantity->__toString(),
            $this->sentAssetIsNft ? 'yes' : 'no',
            $this->receivedAsset,
            $this->receivedQuantity->__toString(),
            $this->receivedAssetIsNft ? 'yes' : 'no',
            $this->feeCurrency,
            $this->feeQuantity->__toString(),
        );
    }
}

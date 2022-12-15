<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\Nft\Actions\AcquireNft;
use Domain\Aggregates\Nft\Actions\DisposeOfNft;
use Domain\Aggregates\Nft\Repositories\NftRepository;
use Domain\Aggregates\Nft\NftId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\NftHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\Traits\AttributesFees;
use Domain\ValueObjects\Transaction;

class NftHandler
{
    use AttributesFees;

    public function __construct(private NftRepository $nftRepository)
    {
    }

    /** @throws NftHandlerException */
    public function handle(Transaction $transaction): void
    {
        $this->validate($transaction);

        if ($transaction->sentAssetIsNft) {
            $this->handleDisposal($transaction);
        }

        if ($transaction->receivedAssetIsNft) {
            $this->handleAcquisition($transaction);
        }
    }

    /** @throws NftHandlerException */
    private function validate(Transaction $transaction): void
    {
        $transaction->isReceive()
            || $transaction->isSend()
            || $transaction->isSwap()
            || throw NftHandlerException::unsupportedOperation($transaction);

        $transaction->receivedAssetIsNft || $transaction->sentAssetIsNft || throw NftHandlerException::noNft($transaction);
    }

    private function handleDisposal(Transaction $transaction): void
    {
        assert($transaction->sentAsset !== null);

        $nftId = NftId::fromNftId($transaction->sentAsset);
        $nft = $this->nftRepository->get($nftId);

        $nft->disposeOf(new DisposeOfNft(
            date: $transaction->date,
            proceeds: $transaction->marketValue->minus($this->splitFees($transaction)), // @phpstan-ignore-line
        ));

        $this->nftRepository->save($nft);
    }

    private function handleAcquisition(Transaction $transaction): void
    {
        assert($transaction->receivedAsset !== null);

        $nftId = NftId::fromNftId($transaction->receivedAsset);
        $nft = $this->nftRepository->get($nftId);

        $nft->acquire(new AcquireNft(
            date: $transaction->date,
            costBasis: $transaction->marketValue->plus($this->splitFees($transaction)), // @phpstan-ignore-line
        ));

        $this->nftRepository->save($nft);
    }
}

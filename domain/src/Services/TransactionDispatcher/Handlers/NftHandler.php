<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\Nft\Actions\AcquireNft;
use Domain\Aggregates\Nft\Actions\DisposeOfNft;
use Domain\Aggregates\Nft\Repositories\NftRepository;
use Domain\Aggregates\Nft\NftId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\NftHandlerException;
use Domain\ValueObjects\Transaction;

class NftHandler
{
    public function __construct(private NftRepository $nftRepository)
    {
    }

    /** @throws NftHandlerException */
    public function handle(Transaction $transaction): void
    {
        $this->validate($transaction);

        if ($transaction->receivedAssetIsNft) {
            $this->handleReceive($transaction);
        }

        if ($transaction->sentAssetIsNft) {
            $this->handleSend($transaction);
        }
    }

    /** @throws NftHandlerException */
    private function validate(Transaction $transaction): void
    {
        $transaction->isReceive()
            || $transaction->isSend()
            || $transaction->isSwap()
            || throw NftHandlerException::invalidTransaction('unsupported operation', $transaction);

        $transaction->receivedAssetIsNft
            || $transaction->sentAssetIsNft
            || throw NftHandlerException::invalidTransaction('neither asset is a NFT', $transaction);
    }

    private function handleReceive(Transaction $transaction): void
    {
        assert($transaction->receivedAsset !== null);

        $nftId = NftId::fromNftId($transaction->receivedAsset);
        $nftAggregate = $this->nftRepository->get($nftId);

        $nftAggregate->acquire(new AcquireNft($transaction->date, $transaction->costBasis));
    }

    private function handleSend(Transaction $transaction): void
    {
        assert($transaction->sentAsset !== null);

        $nftId = NftId::fromNftId($transaction->sentAsset);
        $nftAggregate = $this->nftRepository->get($nftId);

        $nftAggregate->disposeOf(new DisposeOfNft($transaction->date, $transaction->costBasis));
    }
}

<?php

declare(strict_types=1);

namespace Domain\Services\TransactionDispatcher\Handlers;

use Domain\Aggregates\Nft\Actions\AcquireNft;
use Domain\Aggregates\Nft\Actions\DisposeOfNft;
use Domain\Aggregates\Nft\Actions\IncreaseNftCostBasis;
use Domain\Aggregates\Nft\Repositories\NftRepository;
use Domain\Aggregates\Nft\NftId;
use Domain\Services\TransactionDispatcher\Handlers\Exceptions\NftHandlerException;
use Domain\Services\TransactionDispatcher\Handlers\Traits\AttributesFees;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\Transactions\Acquisition;
use Domain\ValueObjects\Transactions\Disposal;
use Domain\ValueObjects\Transactions\Swap;

class NftHandler
{
    use AttributesFees;

    public function __construct(private readonly NftRepository $nftRepository)
    {
    }

    /** @throws NftHandlerException */
    public function handle(Acquisition | Disposal | Swap $transaction): void
    {
        $transaction->hasNft() || throw NftHandlerException::noNft($transaction);

        if ($transaction instanceof Acquisition && $transaction->asset->isNft) {
            $this->handleAcquisition($transaction, $transaction->asset);

            return;
        }

        if ($transaction instanceof Disposal && $transaction->asset->isNft) {
            $this->handleDisposal($transaction, $transaction->asset);

            return;
        }

        assert($transaction instanceof Swap);

        if ($transaction->acquiredAsset->isNft) {
            $this->handleAcquisition($transaction, $transaction->acquiredAsset);
        }

        if ($transaction->disposedOfAsset->isNft) {
            $this->handleDisposal($transaction, $transaction->disposedOfAsset);
        }
    }

    private function handleDisposal(Acquisition | Disposal | Swap $transaction, Asset $asset): void
    {
        $nftId = NftId::fromNftId((string) $asset);
        $nft = $this->nftRepository->get($nftId);

        $nft->disposeOf(new DisposeOfNft(
            date: $transaction->date,
            proceeds: $transaction->marketValue->minus($this->splitFees($transaction)),
        ));

        $this->nftRepository->save($nft);
    }

    private function handleAcquisition(Acquisition | Disposal | Swap $transaction, Asset $asset): void
    {
        $nftId = NftId::fromNftId((string) $asset);
        $nft = $this->nftRepository->get($nftId);

        if ($nft->isAlreadyAcquired()) {
            $nft->increaseCostBasis(new IncreaseNftCostBasis(
                date: $transaction->date,
                costBasisIncrease: $transaction->marketValue->plus($this->splitFees($transaction)),
            ));
        } else {
            $nft->acquire(new AcquireNft(
                date: $transaction->date,
                costBasis: $transaction->marketValue->plus($this->splitFees($transaction)),
            ));
        }

        $this->nftRepository->save($nft);
    }
}

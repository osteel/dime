<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use Domain\Services\ActionRunner\ActionRunner;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;

final readonly class AcquireNonFungibleAsset
{
    public function __construct(
        private Asset $asset,
        public LocalDate $date,
        public FiatAmount $costBasis,
    ) {
    }

    public function handle(NonFungibleAssetRepository $nonFungibleAssetRepository, ActionRunner $runner): void
    {
        $nonFungibleAssetId = NonFungibleAssetId::fromNonFungibleAssetId((string) $this->asset);
        $nonFungibleAsset = $nonFungibleAssetRepository->get($nonFungibleAssetId);

        if ($nonFungibleAsset->isAlreadyAcquired()) {
            $runner->run(new IncreaseNonFungibleAssetCostBasis(
                asset: $this->asset,
                date: $this->date,
                costBasisIncrease: $this->costBasis,
            ));

            return;
        }

        $nonFungibleAsset->acquire($this);
        $nonFungibleAssetRepository->save($nonFungibleAsset);
    }
}

<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Actions;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\Contracts\WithAsset;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use Domain\Services\ActionRunner\ActionRunner;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Stringable;

final readonly class AcquireNonFungibleAsset implements Stringable, WithAsset
{
    public function __construct(
        public Asset $asset,
        public LocalDate $date,
        public FiatAmount $costBasis,
        public bool $forFiat,
    ) {
    }

    public function __invoke(NonFungibleAssetRepository $nonFungibleAssetRepository, ActionRunner $runner): void
    {
        $nonFungibleAssetId = NonFungibleAssetId::fromAsset($this->asset);
        $nonFungibleAsset = $nonFungibleAssetRepository->get($nonFungibleAssetId);

        if ($nonFungibleAsset->isAlreadyAcquired()) {
            $runner->run(new IncreaseNonFungibleAssetCostBasis(
                asset: $this->asset,
                date: $this->date,
                costBasisIncrease: $this->costBasis,
                forFiat: $this->forFiat,
            ));

            return;
        }

        $nonFungibleAsset->acquire($this);
        $nonFungibleAssetRepository->save($nonFungibleAsset);
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (asset: %s, date: %s, cost basis: %s, for fiat: %s)',
            self::class,
            $this->asset,
            $this->date,
            $this->costBasis,
            $this->forFiat ? 'yes' : 'no',
        );
    }
}

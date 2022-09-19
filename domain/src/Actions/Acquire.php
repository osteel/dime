<?php

namespace Domain\Actions;

use App\Action;

class Acquire implements Action
{
	public function __construct(
		public readonly string $nftId,
		public readonly Fiat $costBasis,
	) {
	}

    public function handle(NftRepository $repository): void
	{
		$nft = $repository->retrieve($this->nftId);

		$nft->acquire($this);
	}
}

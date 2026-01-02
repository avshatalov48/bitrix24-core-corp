<?php

declare(strict_types=1);

namespace Bitrix\Baas\Model\Dto;

use \Bitrix\Baas;

class PurchasesSummary implements \Bitrix\Baas\Contract\PurchasesSummary
{
	public function __construct(
		private float $balance,
		private array $purchases = [],
	)
	{
	}

	public function getCount(): int
	{
		return count($this->purchases);
	}

	public function getBalance(): float
	{
		return $this->balance;
	}

	public function &getPurchases(): array
	{
		return $this->purchases;
	}

	public function __serialize(): array
	{
		return [
			'purchaseCount' => $this->getCount(),
			'purchaseBalance' => $this->getBalance(),
			'purchases' => $this->packPurchasedPackages(),
		];
	}

	private function packPurchasedPackages(): array
	{
		$packedPurchases = [];

		/** @var Baas\Model\Dto\Purchase $purchase */
		foreach ($this->purchases as $purchase)
		{
			$purchasedPackages = [];
			/** @var Baas\Model\Dto\PurchasedPackage $purchasedPack */
			foreach ($purchase->getPurchasedPackages() as $purchasedPack)
			{
				$purchasedPackages[] = $purchasedPack->__serialize();
			}
			if (!empty($purchasedPackages))
			{
				$packedPurchases[] = $purchasedPackages;
			}
		}

		return $packedPurchases;
	}
}

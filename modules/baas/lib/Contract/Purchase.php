<?php

declare(strict_types=1);

namespace Bitrix\Baas\Contract;


interface Purchase
{
	public function getCode(): string;

	/**
	 * @return PurchasedPackage[]
	 */
	public function getPurchasedPackages(): array;
}

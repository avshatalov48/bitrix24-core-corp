<?php

declare(strict_types=1);

namespace Bitrix\Baas\Contract;

interface PurchasesSummary
{
	public function getCount(): int;

	public function getBalance(): float;

	/**
	 * @return Purchase[]
	 */
	public function getPurchases(): array;

	public function __serialize(): array;
}

<?php

declare(strict_types=1);

namespace Bitrix\Baas\Contract;

interface PurchaseService
{
	/**
	 * @return array<Purchase>
	 */
	public function getAll(): array;

	/**
	 * @return array<Purchase>
	 */
	public function getByPackageCode(string $code): array;

	/**
	 * @return array<Purchase>
	 */
	public function getNotExpired(): array;
}

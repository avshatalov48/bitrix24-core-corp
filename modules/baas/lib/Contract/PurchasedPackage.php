<?php

declare(strict_types=1);

namespace Bitrix\Baas\Contract;

use Bitrix\Main;

interface PurchasedPackage
{
	public function getCode(): string;

	public function getPackageCode(): string;

	public function getPurchaseCode(): string;

	public function getStartDate(): Main\Type\Date;

	public function getExpirationDate(): Main\Type\Date;

	public function getExpirationFormattedDate(): string;

	public function isActive(): bool;

	public function isActual(): bool;

	/**
	 * @return PurchasedServiceInPackage[]
	 */
	public function getPurchasedServices(): array;
}

<?php

declare(strict_types=1);

namespace Bitrix\Baas\Contract;

interface PurchasedServiceInPackage
{
	public function getServiceCode(): string;

	public function getPurchasedPackage(): PurchasedPackage;

	public function getInitialValue(): int;

	public function getCurrentValue(): int;

	public function getUsageValue(): int;

	public function getUsagePercentage(): float;
}

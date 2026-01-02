<?php

declare(strict_types=1);

namespace Bitrix\Baas\Model\Dto;

class PurchasedServiceInPackage implements \Bitrix\Baas\Contract\PurchasedServiceInPackage
{
	public function __construct(
		private string $serviceCode,
		private PurchasedPackage $purchasedPackage,
		private int $initialValue,
		private int $currentValue,
	)
	{
	}

	public function getServiceCode(): string
	{
		return $this->serviceCode;
	}

	public function getPurchasedPackage(): PurchasedPackage
	{
		return $this->purchasedPackage;
	}

	public function getInitialValue(): int
	{
		return $this->initialValue;
	}

	public function getCurrentValue(): int
	{
		return $this->currentValue;
	}

	public function getUsageValue(): int
	{
		return $this->initialValue - $this->currentValue;
	}

	public function getUsagePercentage(): float
	{
		return (($this->initialValue - $this->currentValue) / $this->initialValue) * 100;
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Baas\Model\Dto;

class Purchase implements \Bitrix\Baas\Contract\Purchase
{
	public function __construct(
		private string $code,
		private array $purchasedPackages = [],
	)
	{
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function &getPurchasedPackages(): array
	{
		return $this->purchasedPackages;
	}
}

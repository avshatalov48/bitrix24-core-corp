<?php

namespace Bitrix\Intranet\Integration\Baas;

use Bitrix\Main;
use Bitrix\Baas;

final class BaasProvider
{
	protected bool $available = false;
	protected ?int $purchaseCount = null;
	protected ?Baas\Baas $baas = null;

	public function __construct()
	{
		if (Main\Loader::includeModule('baas'))
		{
			$this->baas = Baas\Baas::getInstance();
			$this->available = $this->baas->isAvailable();
		}
		else
		{
			$this->available = false;
		}
	}

	public function isAvailable(): bool
	{
		return $this->available;
	}

	public function isActive(): bool
	{
		return $this->available && $this->getPurchaseCount() > 0;
	}

	public function getPurchaseCount(): int
	{
		if (!$this->purchaseCount)
		{
			if ($this->baas)
			{
				$this->purchaseCount = count($this->baas->getNotExpiredPurchases());
			}
			else
			{
				$this->purchaseCount = 0;
			}
		}

		return $this->purchaseCount;
	}
}

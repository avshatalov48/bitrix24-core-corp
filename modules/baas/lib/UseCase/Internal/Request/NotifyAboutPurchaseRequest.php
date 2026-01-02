<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\Internal\Request;

class NotifyAboutPurchaseRequest
{
	public function __construct(
		public string $packageCode,
		public string $purchaseCode,
	)
	{
	}
}

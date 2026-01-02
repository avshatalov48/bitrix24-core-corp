<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\Internal\Request;

class PackAvailablePurchaseInfoRequest
{
	public function __construct(
		public readonly ?string $packageCode = null,
		public readonly ?string $purchaseCode = null,
		public readonly bool $onlyEnabled = true,
		public readonly bool $includeDepleted = false,
	)
	{
	}
}

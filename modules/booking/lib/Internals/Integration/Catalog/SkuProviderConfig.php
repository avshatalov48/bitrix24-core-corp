<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Catalog;

class SkuProviderConfig
{
	public function __construct(
		public readonly bool $loadSections = false,
	)
	{
	}
}

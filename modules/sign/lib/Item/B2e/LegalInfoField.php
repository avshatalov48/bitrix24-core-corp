<?php

namespace Bitrix\Sign\Item\B2e;

use Bitrix\Sign\Contract\Item;

class LegalInfoField implements Item
{
	public function __construct(
		public string $type,
		public string $caption,
		public string $name,
	) {}
}

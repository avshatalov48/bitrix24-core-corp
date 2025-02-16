<?php

namespace Bitrix\Sign\Item;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract\Item;

class ProviderDate implements Item
{
	public function __construct(
		public string $companyUid,
		public DateTime $dateCreate,
	) {}
}

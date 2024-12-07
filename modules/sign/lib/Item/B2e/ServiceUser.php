<?php

namespace Bitrix\Sign\Item\B2e;

use Bitrix\Sign\Contract\Item;

class ServiceUser implements Item
{
	public function __construct(
		public int $userId,
		public string $uid,
	) {}
}

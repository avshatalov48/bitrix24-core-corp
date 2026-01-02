<?php

namespace Bitrix\HumanResources\Item\Access;

use Bitrix\HumanResources\Contract\Item;

class AccessInfo implements Item
{
	public function __construct(
		public string $actionId,
		public string $permissionId,
	) {}
}
<?php

namespace Bitrix\HumanResources\Item\Access;

use Bitrix\HumanResources\Contract\Item;

class Permission implements Item
{
	public function __construct(
		public int $roleId,
		public string $permissionId,
		public int $value,
	) {}
}
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

	public static function getWithoutRoleId(string $permissionId, int $value): Permission
	{
		return new Permission(
			roleId: 0,
			permissionId: $permissionId,
			value: $value,
		);
	}
}
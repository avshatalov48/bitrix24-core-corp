<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Item;

interface RoleHelperService
{
	public function getById(int $roleId): ?Item\Role;
	public function getEmployeeRoleId(): ?int;

	public function getHeadRoleId(): ?int;
}
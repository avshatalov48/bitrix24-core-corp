<?php

namespace Bitrix\HumanResources\Repository\Access;

use Bitrix\HumanResources\Model\Access\AccessRoleTable;
use Bitrix\Main\Entity\DeleteResult;
use Bitrix\Main\Entity\UpdateResult;
use Bitrix\Main\ORM\Data\AddResult;

class RoleRepository implements \Bitrix\HumanResources\Contract\Repository\Access\RoleRepository
{
	public function getRoleList(): array
	{
		return AccessRoleTable::getList([])->fetchAll();
	}

	public function create(string $roleName): AddResult
	{
		return AccessRoleTable::add([
			'NAME' => $roleName,
		]);
	}

	public function delete(int $roleId): DeleteResult
	{
		return AccessRoleTable::delete($roleId);
	}

	public function updateName(int $roleId, string $name): UpdateResult
	{
		return AccessRoleTable::update($roleId, ['NAME' => $name]);
	}

	public function getRoleObjectByName(string $name)
	{
		return AccessRoleTable::query()
			->setFilter(['=NAME' => $name])
			->fetchObject()
		;
	}

	public function areRolesDefined(): bool
	{
		return AccessRoleTable::query()->setSelect(['ID'])->setLimit(1)->fetchObject() !== null;
	}
}
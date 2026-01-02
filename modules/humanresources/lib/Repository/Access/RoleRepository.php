<?php

namespace Bitrix\HumanResources\Repository\Access;

use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Model\Access\AccessRoleTable;
use Bitrix\HumanResources\Model\Access\EO_AccessRole;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DeleteResult;
use Bitrix\Main\Entity\UpdateResult;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\SystemException;

class RoleRepository
{
	public function getRoleList(?RoleCategory $category = null): array
	{
		$parameters = [
			'select' => ['ID', 'NAME', 'CATEGORY'],
		];

		if ($category)
		{
			$parameters['filter'] =  [
					'=CATEGORY' => $category->value,
			];
		}
		return AccessRoleTable::getList($parameters)->fetchAll();
	}

	public function create(string $roleName, RoleCategory $category = RoleCategory::Department): AddResult
	{
		return AccessRoleTable::add([
			'NAME' => $roleName,
			'CATEGORY' => $category->value,
		]);
	}

	public function delete(int $roleId): DeleteResult
	{
		return AccessRoleTable::delete($roleId);
	}

	/**
	 * @param array<int> $roleIds
	 */
	public function deleteByIds(array $roleIds): void
	{
		if (empty($roleIds))
		{
			return;
		}

		AccessRoleTable::deleteList(['@ID' => $roleIds]);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getRoleObjectByNameAndCategory(string $name, RoleCategory $roleCategory): ?EO_AccessRole
	{
		return AccessRoleTable::query()
			->setFilter([
				'=NAME' => $name,
				'=CATEGORY' => $roleCategory->value,
			])
			->fetchObject()
		;
	}

	public function getRoleNameById(int $roleId): ?string
	{
		$role = AccessRoleTable::query()
			->setSelect(['NAME'])
			->where('ID', $roleId)
			->setLimit(1)
			->exec()
			->fetch()
		;

		if ($role && $role['NAME'])
		{
			return $role['NAME'];
		}

		return null;
	}

	public function areRolesDefined(): bool
	{
		return AccessRoleTable::query()->setSelect(['ID'])->setLimit(1)->fetchObject() !== null;
	}
}
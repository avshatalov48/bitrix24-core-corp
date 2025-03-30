<?php

namespace Bitrix\HumanResources\Repository\Access;

use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Model\Access\AccessPermissionTable;
use Bitrix\HumanResources\Item;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\HumanResources\Access\Role\RoleUtil;

class PermissionRepository implements \Bitrix\HumanResources\Contract\Repository\Access\PermissionRepository
{
	/**
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function create(Item\Access\Permission $permission): void
	{
		$permissionEntity = AccessPermissionTable::getEntity()->createObject();

		if (!$permission->value)
		{
			return;
		}

		try
		{
			$permissionCreateResult =
				$permissionEntity
					->setRoleId($permission->roleId)
					->setPermissionId($permission->permissionId)
					->setValue($permission->value)
					->save()
			;
		}
		catch (DuplicateEntryException)
		{
			return;
		}

		if (!$permissionCreateResult->isSuccess())
		{
			throw (new CreationFailedException())
				->setErrors($permissionCreateResult->getErrorCollection())
			;
		}
	}

	/**
	 * @param Item\Collection\Access\PermissionCollection $permissionCollection
	 *
	 * @return void
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function createByCollection(
		Item\Collection\Access\PermissionCollection $permissionCollection
	): void
	{
		$query = [];
		foreach ($permissionCollection as $permission)
		{
			$query[] = [
				'ROLE_ID' => $permission->roleId,
				'PERMISSION_ID' => $permission->permissionId,
				'VALUE' => $permission->value,
			];
		}

		$connection = \Bitrix\Main\Application::getConnection();
		RoleUtil::insertPermissions($query);
	}

	/**
	 * @param array{
	 *     ROLE_ID: int,
	 *     PERMISSION_ID: string,
	 *     VALUE: int,
	 *  } $permission
	 * @return Item\Access\Permission
	 */
	private function convertArrayToItem(array $permission): Item\Access\Permission
	{
		return new Item\Access\Permission(
			roleId: $permission['ROLE_ID'],
			permissionId: $permission['PERMISSION_ID'],
			value: $permission['VALUE'],
		);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getPermissionList(): Item\Collection\Access\PermissionCollection
	{
		$res = AccessPermissionTable::getList([])->fetchAll();
		$permissionCollection = new Item\Collection\Access\PermissionCollection();
		foreach ($res as $row)
		{
			$permissionCollection->add($this->convertArrayToItem($row));
		}

		return $permissionCollection;
	}

	/**
	 * @param array<int> $roleIds
	 * @return void
	 */
	public function deleteByRoleIds(array $roleIds): void
	{
		try
		{
			AccessPermissionTable::deleteList(["=ROLE_ID" => $roleIds]);
		}
		catch (\Exception $e)
		{
		}
	}

	/**
	 * @param array<int> $roleIds
	 * @return array<array-key, int>
	 */
	public function getPermissionsByRoleIds(array $roleIds): array
	{
		$permissions = [];

		foreach (AccessPermissionTable::query()
			->addSelect("PERMISSION_ID")
			->addSelect("VALUE")
			->whereIn("ROLE_ID", $roleIds)
			->fetchAll() as $row)
		{
			$permissionId = (string)$row["PERMISSION_ID"];
			$value = (int)$row["VALUE"];
			if (!array_key_exists($permissionId, $permissions))
			{
				$permissions[$permissionId] = 0;
			}
			if ($value > $permissions[$permissionId])
			{
				$permissions[$permissionId] = $value;
			}
		}

		return $permissions;
	}

	public function setPermissionByRoleId(int $roleId, string $permissionId, int $value): void
	{
		$rolePermissionId = AccessPermissionTable::query()
			->addSelect("ID")
			->where("ROLE_ID", $roleId)
			->where("PERMISSION_ID", $permissionId)
			->fetch()
		;

		if (!$rolePermissionId)
		{
			$rolePermissionItem = new Item\Access\Permission(
				roleId: (int)$roleId,
				permissionId: $permissionId,
				value: (int)$value,
			);

			$this->create($rolePermissionItem);

			return;
		}
		AccessPermissionTable::update(
			$rolePermissionId["ID"],
			[
				"VALUE" => $value,
			]
		);
	}
}
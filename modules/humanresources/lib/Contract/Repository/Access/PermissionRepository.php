<?php

namespace Bitrix\HumanResources\Contract\Repository\Access;

use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

interface PermissionRepository
{
	/**
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function create(Item\Access\Permission $permission): void;

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws CreationFailedException
	 * @throws SystemException
	 */
	public function createByCollection(
		Item\Collection\Access\PermissionCollection $permissionCollection
	): void;

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getPermissionList(): Item\Collection\Access\PermissionCollection;

	/**
	 * @param array<int> $roleIds
	 * @return void
	 */
	public function deleteByRoleIds(array $roleIds): void;

	/**
	 * @param array<int> $roleIds
	 * @return array<array-key, int>
	 */
	public function getPermissionsByRoleIds(array $roleIds): array;

	public function setPermissionByRoleId(int $roleId, string $permissionId, int $value): void;
}
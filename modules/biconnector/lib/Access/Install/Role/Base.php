<?php

namespace Bitrix\BIConnector\Access\Install\Role;

use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Access\Role\RoleDictionary;
use Bitrix\BIConnector\Access\Role\RoleRelationTable;
use Bitrix\BIConnector\Access\Role\RoleTable;
use Bitrix\BIConnector\Access\Role\RoleUtil;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\Main\Result;

abstract class Base
{
	public function __construct(protected readonly string $code, protected readonly bool $isNewPortal = false)
	{
	}

	/**
	 * @return array
	 */
	abstract protected function getPermissions(): array;

	abstract protected function getRelationUserGroups(): array;

	abstract protected function getDefaultGroupPermissions(): array;

	protected function getGroupPermissions(): array
	{
		$groupList = SupersetDashboardGroupTable::getList([
			'select' => ['ID', 'CODE'],
			'filter' => [
				'TYPE' => SupersetDashboardGroupTable::GROUP_TYPE_SYSTEM,
			],
			'cache' => [
				'ttl' => 60,
			],
		])
			->fetchAll()
		;

		$groupRelation = array_column($groupList, 'ID', 'CODE');

		$groupPermissionsList = [];
		foreach ($this->getDefaultGroupPermissions() as $groupCode => $groupPermissions)
		{
			if (!isset($groupRelation[$groupCode]))
			{
				continue;
			}
			$permissionId = PermissionDictionary::getDashboardGroupPermissionId($groupRelation[$groupCode]);
			foreach ($groupPermissions as $permission)
			{
				$groupPermissionsList[] = [
					'permissionId' => $permissionId,
					'value' => $permission,
				];
			}
		}

		return $groupPermissionsList;
	}

	/**
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	public function getMap(): array
	{
		$result = [];
		foreach ($this->getPermissions() as $permissionId)
		{
			$result[] = [
				'permissionId' => $permissionId,
				'value' => PermissionDictionary::getDefaultPermissionValue($permissionId),
			];
		}

		$groupPermission = $this->getGroupPermissions();
		$result = array_merge($result, $groupPermission);

		return $result;
	}

	public function install(): Result
	{
		$result = RoleTable::add([
			'NAME' => RoleDictionary::getRoleName($this->code) ?? $this->code,
		]);

		if (!$result->isSuccess())
		{
			return $result;
		}

		$query = [];
		$roleId = $result->getId();
		foreach ($this->getMap() as $item)
		{
			$query[] = [
				'ROLE_ID' => $roleId,
				'PERMISSION_ID' => $item['permissionId'],
				'VALUE' => $item['value'],
			];
		}

		RoleUtil::insertPermissions($query);

		$permissionCodes = $this->getRelationUserGroups();
		foreach ($permissionCodes as $code)
		{
			$resultAdd = RoleRelationTable::add([
				'ROLE_ID' => $roleId,
				'RELATION' => $code,
			]);

			if (!$resultAdd->isSuccess())
			{
				return $resultAdd;
			}
		}

		return $result;
	}
}

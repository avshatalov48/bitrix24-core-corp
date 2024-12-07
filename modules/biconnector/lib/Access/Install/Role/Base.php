<?php

namespace Bitrix\BIConnector\Access\Install\Role;

use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Access\Role\RoleDictionary;
use Bitrix\BIConnector\Access\Role\RoleRelationTable;
use Bitrix\BIConnector\Access\Role\RoleTable;
use Bitrix\BIConnector\Access\Role\RoleUtil;
use Bitrix\Main\Result;

abstract class Base
{
	public function __construct(protected string $code, protected bool $isNewPortal = false)
	{
	}

	/**
	 * @return array
	 */
	abstract protected function getPermissions(): array;
	abstract protected function getRelationUserGroups(): array;

	public function getMap(): array
	{
		$result = [];
		foreach ($this->getPermissions() as $permissionId)
		{
			$result[] = [
				'permissionId' => $permissionId,
				'value' => PermissionDictionary::getDefaultPermissionValue($permissionId)
			];
		}

		return $result;
	}

	public function install(): Result
	{
		$result = RoleTable::add([
			'NAME' => RoleDictionary::getRoleName($this->code) ?? $this->code
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
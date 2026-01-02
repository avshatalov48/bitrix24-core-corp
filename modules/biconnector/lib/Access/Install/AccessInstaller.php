<?php

namespace Bitrix\BIConnector\Access\Install;

use Bitrix\BIConnector\Access\Permission\PermissionTable;
use Bitrix\BIConnector\Access\Role\RoleRelationTable;
use Bitrix\BIConnector\Access\Role\RoleTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupBindingTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupScopeTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class AccessInstaller
{
	public static function install($isNewPortal = true, ?RoleMap $roleMap = null): Result
	{
		$result = new Result();
		if ($roleMap === null)
		{
			$roleMap = self::getRoleMap($isNewPortal);
		}

		$installGroupResult = self::installDefaultGroups();
		if (!$installGroupResult->isSuccess())
		{
			$result->addErrors($installGroupResult->getErrors());

			return $result;
		}

		foreach ($roleMap->getRoles() as $role)
		{
			$installRoleResult = $role->install();
			if (!$installRoleResult->isSuccess())
			{
				foreach ($installRoleResult->getErrors() as $error)
				{
					$result->addError(new Error($error->getMessage(), $error->getCode(), ['ROLE_NAME' => $role->getCode()]));
				}
			}
		}

		return $result;
	}

	public static function reinstall($isNewPortal = false, ?RoleMap $roleMap = null): void
	{
		self::clearRelations();
		self::install($isNewPortal, $roleMap);
	}

	public static function getDefaultGroupName(string $groupCode, ?string $language = null): string
	{
		return Loc::getMessage('BICONNECTOR_SYSTEM_GROUP_NAME_' . strtoupper($groupCode), null, $language) ?? '';
	}

	protected static function getRoleMap($isNewPortal = true): RoleMap
	{
		return new RoleMap($isNewPortal);
	}

	public static function clearRelations(): void
	{
		RoleRelationTable::deleteList(['>ID' => 0]);
		RoleTable::deleteList(['>ID' => 0]);
		PermissionTable::deleteList(['>ID' => 0]);
		SupersetDashboardGroupTable::deleteByFilter(['>ID' => 0]);
		SupersetDashboardGroupScopeTable::deleteByFilter(['>ID' => 0]);
		SupersetDashboardGroupBindingTable::deleteByFilter(['>ID' => 0]);
	}

	private static function installDefaultGroups(): Result
	{
		$result = new Result();

		$systemGroups = SupersetDashboardGroupTable::getList([
			'filter' => ['=TYPE' => SupersetDashboardGroupTable::GROUP_TYPE_SYSTEM],
			'select' => ['CODE'],
		])
			->fetchAll()
		;

		$existedSystemCodes = array_column($systemGroups, 'CODE');

		$newSystemCodeList = array_diff(ScopeService::getSystemGroupCode(), $existedSystemCodes);

		foreach ($newSystemCodeList as $groupCode)
		{
			$group = SupersetDashboardGroupTable::createObject();
			$group->setCode($groupCode);
			$group->setType(SupersetDashboardGroupTable::GROUP_TYPE_SYSTEM);
			$group->setName(self::getDefaultGroupName($groupCode));

			$saveGroupResult = $group->save();
			if (!$saveGroupResult->isSuccess())
			{
				$result->addErrors($saveGroupResult->getErrors());

				return $result;
			}

			$scope = SupersetDashboardGroupScopeTable::createObject();
			$scope->setGroup($group);
			$scope->setScopeCode($groupCode);
			$saveScopeResult = $scope->save();
			if (!$saveScopeResult->isSuccess())
			{
				$result->addErrors($saveScopeResult->getErrors());

				return $result;
			}
		}

		return $result;
	}
}

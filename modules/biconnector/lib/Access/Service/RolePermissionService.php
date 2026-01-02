<?php

namespace Bitrix\BIConnector\Access\Service;

use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Access\Permission\PermissionTable;
use Bitrix\BIConnector\Access\Role\RoleTable;
use Bitrix\BIConnector\Access\Role\RoleUtil;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Text\Encoding;

final class RolePermissionService
{
	private const DB_ERROR_KEY = 'BICONNECTOR_CONFIG_PERMISSIONS_DB_ERROR';
	private RoleRelationService $roleRelationService;

	public function __construct()
	{
		$this->roleRelationService = new RoleRelationService();
	}

	/**
	 * @param array $permissionSettings
	 * @param array $accessRights
	 *
	 * @return Result
	 */
	public function saveRolePermissions(array $permissionSettings, array $accessRights = []): Result
	{
		$query = [];
		$roles = [];
		$result = new Result();
		$db = Application::getConnection();

		try
		{
			$db->startTransaction();

			$groupPermissionIdList = array_reduce($accessRights, static function ($carry, $permission) {
				if (PermissionDictionary::isDashboardGroupPermission($permission['id']))
				{
					$carry[] = $permission['additionalRightData']['group'];
				}

				return $carry;
			}, []);

			if (!empty($groupPermissionIdList))
			{
				$dashboardGroupMap = [];
				foreach ($groupPermissionIdList as $group)
				{
					$groupInfo = [
						'id' => str_starts_with($group['id'], 'new_')
							? null
							: PermissionDictionary::getDashboardGroupIdFromPermission($group['id']),
						'name' => $group['name'],
					];
					$scopeList = isset($group['scopes'])
						? array_column($group['scopes'], 'code')
						: [];
					$dashboards = $group['dashboards'] ?? [];

					$saveResult = DashboardGroupService::saveGroup(
						$groupInfo,
						$scopeList,
						$dashboards
					);
					if (!$saveResult->isSuccess())
					{
						$result->addErrors($saveResult->getErrors());
						$db->rollbackTransaction();

						return $result;
					}
					$dashboardGroupMap[$group['id']] = $saveResult->getData()['id'];
				}

				$permissionSettings = array_map(static function ($userGroup) use ($dashboardGroupMap) {
					foreach ($userGroup['accessRights'] as &$accessRight)
					{
						if (str_starts_with($accessRight['id'], 'new_G'))
						{
							$accessRight['id'] = PermissionDictionary::getDashboardGroupPermissionId(
								$dashboardGroupMap[$accessRight['id']]
							);
						}
					}

					return $userGroup;
				}, $permissionSettings);
			}

			foreach ($permissionSettings as &$setting)
			{
				$roleId = (int)$setting['id'];
				$roleTitle = (string)$setting['title'];

				$saveRoleResult = $this->saveRole($roleTitle, $roleId);
				if (!$saveRoleResult->isSuccess())
				{
					$result->addErrors($saveRoleResult->getErrors());
					$db->rollbackTransaction();

					return $result;
				}
				$roleId = $saveRoleResult->getData()['id'];

				$setting['id'] = $roleId;
				$roles[] = $roleId;

				if (!isset($setting['accessRights']))
				{
					continue;
				}

				foreach ($setting['accessRights'] as $permission)
				{
					$permissionId = $permission['id'];

					$query[] = [
						'ROLE_ID' => $roleId,
						'PERMISSION_ID' => $permissionId,
						'VALUE' => $permission['value'],
					];
				}
			}
			unset($setting);

			if ($query)
			{
				if (!PermissionTable::deleteList(['=ROLE_ID' => $roles]))
				{
					$result->addError(new Error(Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_DB_ERROR')));
					$db->rollbackTransaction();

					return $result;
				}

				RoleUtil::insertPermissions($query);
				if (\Bitrix\Main\Loader::includeModule('intranet'))
				{
					\CIntranetUtils::clearMenuCache();
				}

				$this->roleRelationService->saveRoleRelation($permissionSettings);
			}

			$db->commitTransaction();
			$result->setData(['permissionSettings' => $permissionSettings]);
		}
		catch (\Exception $e)
		{
			$db->rollbackTransaction();
			$result->addError(new Error($e->getMessage()));
		}

		return $result;
	}

	/**
	 * @param string $name Role name.
	 * @param int|null $roleId Role identification number.
	 *
	 * @return Result
	 */
	public function saveRole(string $name, int $roleId = null): Result
	{
		$result = new Result();

		if (empty($name))
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_ACCESS_SAVE_ROLE_ERROR_NO_NAME')));

			return $result;
		}
		$nameField = [
			'NAME' => Encoding::convertEncodingToCurrent($name),
		];

		try
		{
			if ($roleId)
			{
				$role = RoleTable::update($roleId, $nameField);
			}
			else
			{
				$role = RoleTable::add($nameField);
			}
		}
		catch (\Exception $e)
		{
			$result->addError(new Error('Role adding failed'));
			\CEventLog::add([
				'SEVERITY' => 'ERROR',
				'AUDIT_TYPE_ID' => self::DB_ERROR_KEY,
				'MODULE_ID' => 'biconnector',
				'DESCRIPTION' => "Error role adding. Role id: {$roleId}. Role name: {$name}. Exception: {$e->getMessage()}",
			]);

			return $result;
		}

		$result->setData(['id' => $role->getId()]);

		return $result;
	}

	/**
	 * Deletes a role by id.
	 * @param int $roleId
	 *
	 * @return Result
	 * @throws SqlQueryException
	 */
	public function deleteRole(int $roleId): Result
	{
		$result = new Result();
		$connection = Application::getConnection();
		try
		{
			$connection->startTransaction();

			PermissionTable::deleteList(['=ROLE_ID' => $roleId]);
			$this->roleRelationService->deleteRoleRelations($roleId);
			RoleTable::delete($roleId);

			$connection->commitTransaction();
		}
		catch (\Exception $e)
		{
			$connection->rollbackTransaction();
			\CEventLog::add([
				'SEVERITY' => 'ERROR',
				'AUDIT_TYPE_ID' => self::DB_ERROR_KEY,
				'MODULE_ID' => 'biconnector',
				'DESCRIPTION' => "Error role deleting. Role id: {$roleId}. Exception: {$e->getMessage()}",
			]);
			$result->addError(new Error('Role deleting failed.'));

			return $result;
		}

		return $result;
	}

	public function deletePermissionsByDashboard(int $dashboardId): void
	{
		if ($dashboardId > 0)
		{
			PermissionTable::deleteList([
				'=VALUE' => $dashboardId,
				'@PERMISSION_ID' => array_values(ActionDictionary::getDashboardPermissionsMap()),
			]);
		}
	}
}

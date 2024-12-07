<?php

namespace Bitrix\BIConnector\Access\Service;

use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Permission\PermissionTable;
use Bitrix\BIConnector\Access\Role\RoleTable;
use Bitrix\BIConnector\Access\Role\RoleUtil;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Error;
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
	 *
	 * @return Result
	 * @throws SqlQueryException
	 */
	public function saveRolePermissions(array $permissionSettings): Result
	{
		$query = [];
		$roles = [];
		$result = new Result();

		foreach ($permissionSettings as &$setting)
		{
			$roleId = (int)$setting['id'];
			$roleTitle = (string)$setting['title'];

			$saveRoleResult = $this->saveRole($roleTitle, $roleId);
			if (!$saveRoleResult->isSuccess())
			{
				$result->addErrors($saveRoleResult->getErrors());
				continue;
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
				$permissionId = (int)$permission['id'];

				if ($permissionId < 1)
				{
					continue;
				}

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
			$db = Application::getConnection();

			try
			{
				$db->startTransaction();
				if (!PermissionTable::deleteList(['=ROLE_ID' => $roles]))
				{
					throw new SqlQueryException(self::DB_ERROR_KEY);
				}

				RoleUtil::insertPermissions($query);
				if (\Bitrix\Main\Loader::includeModule('intranet'))
				{
					\CIntranetUtils::clearMenuCache();
				}

				$this->roleRelationService->saveRoleRelation($permissionSettings);

				$db->commitTransaction();
			}
			catch (\Exception $e)
			{
				$db->rollbackTransaction();
				$result->addError(new Error('Saving permissions failed.'));
				\CEventLog::add([
					'SEVERITY' => 'ERROR',
					'AUDIT_TYPE_ID' => self::DB_ERROR_KEY,
					'MODULE_ID' => 'biconnector',
					'DESCRIPTION' => "Error saving permissions. Exception: {$e->getMessage()}. Permissions: " . json_encode($permissionSettings),
				]);
			}
		}
		$result->setData(['permissionSettings' => $permissionSettings]);

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
		$nameField = [
			'NAME' => Encoding::convertEncodingToCurrent($name),
		];
		$result = new Result();

		try
		{
			if ($roleId)
			{
				$role = RoleTable::update($roleId, $nameField);
			}
			else
			{
				$role = RoleTable::getList([
					'filter' => [
						'=NAME' => $nameField['NAME'],
					]
				])->fetchObject();

				if (!$role)
				{
					$role = RoleTable::add($nameField);
				}
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

<?php

namespace Bitrix\ImOpenlines\Security;

use Bitrix\Main\ArgumentException;
use Bitrix\ImOpenlines\Model\RoleAccessTable;
use Bitrix\ImOpenlines\Model\RolePermissionTable;
use Bitrix\ImOpenlines\Model\RoleTable;

class RoleManager
{
	protected static $userRoles = array(); // 'USER_ID' => 'ROLE_ID'

	/**
	 * @param $userId
	 * @return array|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @internal
	 */
	public static function getUserRoles($userId)
	{
		if(isset(self::$userRoles[$userId]))
			return self::$userRoles[$userId];

		$result = array();
		$userAccessCodes = \CAccess::GetUserCodesArray($userId);

		if(!is_array($userAccessCodes) || count($userAccessCodes) === 0)
			return array();

		$cursor = RoleAccessTable::getList(array(
			'filter' => array(
				'=ACCESS_CODE' => $userAccessCodes
			)
		));

		while($row = $cursor->fetch())
		{
			$result[] = $row['ROLE_ID'];
		}

		self::$userRoles[$userId] = $result;
		return $result;
	}

	/**
	 * @param $roleId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @internal
	 */
	public static function getRolePermissions($roleId)
	{
		$cursor = RolePermissionTable::getList(array(
			'filter' => array(
				'=ROLE_ID' => $roleId
			)
		));

		$result = array();
		while ($row = $cursor->fetch())
		{
			$result[$row['ENTITY']][$row['ACTION']] = $row['PERMISSION'];
		}

		return $result;
	}
	
	public static function setRolePermissions($roleId, array $permissions)
	{
		$roleId = (int)$roleId;
		if($roleId <= 0)
			throw new ArgumentException('Role id should be greater than zero', 'roleId');

		$normalizedPermissions = Permissions::getNormalizedPermissions($permissions);
		RolePermissionTable::deleteByRoleId($roleId);
		foreach ($normalizedPermissions as $entity => $actions)
		{
			foreach ($actions as $action => $permission)
			{
				RolePermissionTable::add(array(
					'ROLE_ID' => $roleId,
					'ENTITY' => $entity,
					'ACTION' => $action,
					'PERMISSION' => $permission
				));
			}
		}
		Helper::clearMenuCache();
	}

	/**
	 *
	 * @param $userId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @internal
	 */
	public static function getUserPermissions($userId)
	{
		//administrators should have full access despite everything
		if(\Bitrix\ImOpenLines\Common::hasAccessForAdminPages())
			return self::getAdminPermissions();

		//everybody else's permissions are defined by their role
		$result = array();
		$userAccessCodes = \CAccess::GetUserCodesArray($userId);

		if(!is_array($userAccessCodes) || count($userAccessCodes) === 0)
			return array();

		$cursor = RolePermissionTable::getList(array(
			'filter' => array(
				'=ROLE_ACCESS.ACCESS_CODE' => $userAccessCodes
			)
		));

		while($row = $cursor->fetch())
		{
			if (   !isset($result[$row['ENTITY']][$row['ACTION']])
				|| $result[$row['ENTITY']][$row['ACTION']] < $row['PERMISSION'])
			{
				$result[$row['ENTITY']][$row['ACTION']] = $row['PERMISSION'];
			}
		}

		return $result;
	}

	/**
	 * Returns maximum available permissions
	 * @return array
	 */
	protected static function getAdminPermissions()
	{
		$result = array();
		$permissionMap = Permissions::getMap();

		foreach ($permissionMap as $entity => $actions)
		{
			foreach ($actions as $action => $permissions)
			{
				foreach ($permissions as $permission)
				{
					if(!isset($result[$entity][$action]) || $result[$entity][$action] < $permission)
						$result[$entity][$action] = $permission;
				}
			}
		}

		return $result;
	}

	/**
	 * Deletes role and all dependent records.
	 * @param int $roleId Id of the role
	 * @return null
	 */
	public static function deleteRole($roleId)
	{
		RolePermissionTable::deleteByRoleId($roleId);
		RoleAccessTable::deleteByRoleId($roleId);
		RoleTable::delete($roleId);
		Helper::clearMenuCache();
	}
}
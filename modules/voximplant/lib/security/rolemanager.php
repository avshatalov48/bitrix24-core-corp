<?php

namespace Bitrix\Voximplant\Security;

use Bitrix\Main\ArgumentException;
use Bitrix\Voximplant\Limits;
use Bitrix\Voximplant\Model\RoleAccessTable;
use Bitrix\Voximplant\Model\RolePermissionTable;
use Bitrix\Voximplant\Model\RoleTable;

class RoleManager
{
	protected static $userRoles = []; // 'USER_ID' => 'ROLE_ID'
	protected static $roles; // 'ROLE_ID' => 'NAME'
	protected static $permissions; // array:  ['ROLE_ID']['ENTITY']['ACTION']['PERMISSION']
	protected static $accessCodeToRole; //array: 'ACCESS_CODE' => string[] 'ROLES'

	protected static $cacheTtl = 86400;

	public static function loadRoles()
	{
		if(is_array(static::$roles))
		{
			return;
		}

		if(Helper::canUse())
		{
			$cursor = RoleTable::getList([
				'cache' => [
					'ttl' => static::$cacheTtl
				]
			]);
			while ($row = $cursor->fetch())
			{
				static::$roles[$row['ID']] = $row['NAME'];
			}
		}
		else
		{
			foreach (Helper::getDefaultRoles() as $roleId => $roleFields)
			{
				static::$roles[$roleId] = $roleFields['NAME'];
			}
		}
	}

	public static function loadPermission()
	{
		if(is_array(static::$permissions))
		{
			return;
		}

		if(Helper::canUse())
		{
			$cursor = RolePermissionTable::getList([
				'cache' => [
					'ttl' => static::$cacheTtl
				]
			]);
			while ($row = $cursor->fetch())
			{
				static::$permissions[$row['ROLE_ID']][$row['ENTITY']][$row['ACTION']] = $row['PERMISSION'];
			}
		}
		else
		{
			foreach (Helper::getDefaultRoles() as $roleId => $roleFields)
			{
				foreach ($roleFields['PERMISSIONS'] as $entity => $actions)
				{
					foreach ($actions as $action => $permission)
					{
						static::$permissions[$roleId][$entity][$action] = $permission;
					}
				}
			}
		}
	}

	public static function loadRoleAccess()
	{
		if(is_array(static::$accessCodeToRole))
		{
			return;
		}

		if(Helper::canUse())
		{
			$cursor = RoleAccessTable::getList([
				'cache' => [
					'ttl' => static::$cacheTtl
				]
			]);
			while($row = $cursor->fetch())
			{
				static::$accessCodeToRole[$row['ACCESS_CODE']] ??= [];

				static::$accessCodeToRole[$row['ACCESS_CODE']][] = $row['ROLE_ID'];
			}
		}
		else
		{
			foreach (Helper::getDefaultRoleAccess() as $row)
			{
				static::$accessCodeToRole[$row['ACCESS_CODE']][] = $row['ROLE'];
			}
		}
	}

	/**
	 * @param $userId
	 * @return array|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @internal
	 */
	public static function getUserRoles($userId)
	{
		if(isset(self::$userRoles[$userId]))
		{
			return self::$userRoles[$userId];
		}

		static::loadPermission();
		static::loadRoleAccess();

		$result = [];
		$userAccessCodes = \CAccess::GetUserCodesArray($userId);

		if(!is_array($userAccessCodes) || empty($userAccessCodes))
		{
			return [];
		}

		foreach ($userAccessCodes as $accessCode)
		{
			if(isset(static::$accessCodeToRole[$accessCode]))
			{
				$result = array_merge($result, static::$accessCodeToRole[$accessCode]);
			}
		}
		$result = array_unique($result);

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
		static::loadPermission();
		return static::$permissions[$roleId] ?? [];
	}
	
	public static function setRolePermissions($roleId, array $permissions)
	{
		$roleId = (int)$roleId;
		if($roleId <= 0)
		{
			throw new ArgumentException('Role id should be greater than zero', 'roleId');
		}

		$normalizedPermissions = Permissions::getNormalizedPermissions($permissions);
		RolePermissionTable::deleteByRoleId($roleId);
		if(static::$permissions)
		{
			static::$permissions[$roleId] = [];
		}
		foreach ($normalizedPermissions as $entity => $actions)
		{
			foreach ($actions as $action => $permission)
			{
				RolePermissionTable::add([
					'ROLE_ID' => $roleId,
					'ENTITY' => $entity,
					'ACTION' => $action,
					'PERMISSION' => $permission
				]);

				if(static::$permissions)
				{
					static::$permissions[$roleId][$entity][$action] = $permission;
				}
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
		if(Helper::isAdmin($userId))
		{
			return self::getAdminPermissions();
		}

		//everybody else's permissions are defined by their role
		$result = [];

		$userRoles = static::getUserRoles($userId);
		foreach ($userRoles as $roleId)
		{
			foreach (static::$permissions[$roleId] as $entity => $actions)
			{
				foreach ($actions as $action => $permission)
				{
					if (   !isset($result[$entity][$action])
						|| $result[$entity][$action] < $permission)
					{
						$result[$entity][$action] = $permission;
					}
				}
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
		$result = [];
		$permissionMap = Permissions::getMap();

		foreach ($permissionMap as $entity => $actions)
		{
			foreach ($actions as $action => $permissions)
			{
				foreach ($permissions as $permission)
				{
					if(!isset($result[$entity][$action]) || $result[$entity][$action] < $permission)
					{
						$result[$entity][$action] = $permission;
					}
				}
			}
		}

		return $result;
	}

	public static function getRoleName($roleId)
	{
		static::loadRoles();

		return static::$roles[$roleId];
	}

	public static function getRoles()
	{
		static::loadRoles();
		return static::$roles;
	}

	public static function getPermissions()
	{
		static::loadPermission();
		return static::$permissions;
	}

	public static function getRoleAccess()
	{
		static::loadRoleAccess();
		return static::$accessCodeToRole;
	}

	public static function clearRoleAccess()
	{
		RoleAccessTable::truncate();
		static::$accessCodeToRole = null;
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
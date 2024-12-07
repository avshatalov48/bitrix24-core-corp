<?php

namespace Bitrix\Crm\Security\Role;

use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Security\Role\Model\RoleTable;
use Bitrix\Main;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;

class RolePermission
{
	private static $cache = null;

	public static function getPermissionsByRoles(array $roleIds): array
	{
		if (empty($roleIds))
		{
			return [];
		}

		$rolePermissions = \Bitrix\Crm\Security\Role\Model\RolePermissionTable::getList([
			'filter' => [
				'@ROLE_ID' => $roleIds,
			],
			'select' => [
				'ENTITY',
				'FIELD',
				'FIELD_VALUE',
				'ATTR',
				'PERM_TYPE',
				'SETTINGS',
			],
			'cache' => [
				'ttl' => 84600,
			]
		]);

		$result = [];
		while ($permission = $rolePermissions->fetch())
		{
			$attribute = trim((string)$permission['ATTR']);
			$field = (string)$permission['FIELD'];
			$fieldValue = (string)$permission['FIELD_VALUE'];
			$entity = (string)$permission['ENTITY'];
			$permissionType = (string)$permission['PERM_TYPE'];
			$settings = $permission['SETTINGS'] ?? [];

			if ($field == '-')
			{
				if (!isset($result[$entity][$permissionType][$field])
					|| $attribute > $result[$entity][$permissionType][$field])
				{
					$result[$entity][$permissionType][$field] = $attribute;
				}
			}
			else
				if (!isset($result[$entity][$permissionType][$field][$fieldValue])
					|| $attribute > $result[$entity][$permissionType][$field][$fieldValue])
				{
					$result[$entity][$permissionType][$field][$fieldValue] = $attribute;
				}

			if ($permissionType !== (new Transition([]))->code())
			{
				continue;
			}

			if ($field === '-')
			{
				if (!isset($result[$entity][$permissionType][$field]))
				{
					$result['settings'][$entity][$permissionType][$field] = $settings ?? [];
				} else
				{
					$values = array_unique(array_merge($settings, $result['settings'][$entity][$permissionType][$field] ?? []));
					$result['settings'][$entity][$permissionType][$field] = array_filter($values);
				}
			}
			else if (!isset($result[$entity][$permissionType][$field][$fieldValue]))
			{
				$result['settings'][$entity][$permissionType][$field][$fieldValue] = $settings ?? [];
			}
			else
			{
				$values = array_unique(array_merge($settings, $result['settings'][$entity][$permissionType][$field][$fieldValue] ?? []));
				$result['settings'][$entity][$permissionType][$field][$fieldValue] = array_filter($values);
			}
		}

		return $result;
	}

	public static function getAll()
	{
		if (static::$cache !== null)
		{
			return static::$cache;
		}

		$dbRes = RolePermissionTable::getList([
			"select" => ["*"],
			"filter" => [],
			"cache" => [
				"ttl" => 84600,
			]
		]);
		$result = [];
		while ($res = $dbRes->fetch())
		{
			if (!array_key_exists($res["ROLE_ID"], $result))
			{
				$result[$res["ROLE_ID"]] = [];
			}
			$role = &$result[$res["ROLE_ID"]];

			if ($res['FIELD'] != '-')
			{
				$role[$res['ENTITY']][$res['PERM_TYPE']][$res['FIELD']][$res['FIELD_VALUE']] = [
					'ATTR' => trim($res['ATTR']),
					'SETTINGS' => empty($res['SETTINGS']) ? null : $res['SETTINGS'],
				];
			}
			else
			{
				$role[$res['ENTITY']][$res['PERM_TYPE']][$res['FIELD']] =  [
					'ATTR' => trim($res['ATTR']),
					'SETTINGS' => empty($res['SETTINGS']) ? null : $res['SETTINGS'],
				];;
			}
		}
		static::$cache = $result;

		return $result;
	}

	/**
	 * @param string $entityId
	 * @return array it is an array like [roleId => ["READ" => ["-" => "X"], ...]]]
	 */
	public static function getByEntityId(string $entityId, bool $skipSystemRoles = true)
	{
		$result = [];
		$systemRolesIds = self::getSystemRolesIds();

		foreach (self::getAll() as $roleId => $entities)
		{
			if (in_array($roleId, $systemRolesIds, false) && $skipSystemRoles)
			{
				continue;
			}

			if (array_key_exists($entityId, $entities))
			{
				$result[$roleId] = $entities[$entityId];
			}
			else
			{
				$categoryIdentifier = \Bitrix\Crm\Category\PermissionEntityTypeHelper::extractEntityEndCategoryFromPermissionEntityType($entityId);
				if ($categoryIdentifier)
				{
					$result[$roleId] = \CCrmRole::getDefaultPermissionSetForEntity($categoryIdentifier);
				}
			}
		}

		return $result;
	}

	/**
	 * Sets a permission from the set for certain roles but one entity
	 *
	 * @param string $entityId
	 * @param array $permissionSet it is an array like [roleId => ["READ" => ["-" => "X"], ...]]]
	 * @param bool $skipAdminRoles Skip roles with "Allow edit config" checkbox to avoid decreasing permissions level in them
	 * @return Main\Result
	 */
	public static function setByEntityId(string $entityId, array $permissionSet, $skipAdminRoles = false, $skipSystemRoles = true)
	{
		static::$cache = null;
		$systemRolesIds = self::getSystemRolesIds();

		$result = new Main\Result();

		$role = new \CCrmRole();
		foreach (self::getAll() as $roleId => $entities)
		{
			if (
				$skipAdminRoles
				&& array_key_exists("CONFIG", $entities)
				&& array_key_exists("WRITE", $entities["CONFIG"])
			)
			{
				$perms = reset($entities["CONFIG"]["WRITE"]);
				$adminPermValue = is_array($perms) ? $perms['ATTR'] : $perms;
				if ($adminPermValue >= \Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL)
				{
					continue;
				}
			}
			if (in_array($roleId, $systemRolesIds, false) && $skipSystemRoles) // do not affect system roles
			{
				continue;
			}

			if (array_key_exists($roleId, $permissionSet))
			{
				$entities[$entityId] = $permissionSet[$roleId];

				$fields = ["RELATION" => $entities];
				if (!$role->Update($roleId, $fields))
				{
					$result->addError(new Main\Error($fields["RESULT_MESSAGE"]));
				}
			}
		}

		return $result;
	}

	/**
	 * Sets the same permission for all roles but one entity
	 *
	 * @param string $entityId
	 * @param array $permissionSet it is an array like ["READ" => ["-" => ["ATTR" => "X"]], ...]]
	 * @return Main\Result
	 */
	public static function setByEntityIdForAllNotAdminRoles(string $entityId, array $permissionSet)
	{
		static::$cache = null;

		$systemRolesIds = self::getSystemRolesIds();
		$result = new Main\Result();

		$role = new \CCrmRole();
		foreach (self::getAll() as $roleId => $entities)
		{
			if (array_key_exists("CONFIG", $entities) && array_key_exists("WRITE", $entities["CONFIG"]))
			{
				$perms = reset($entities["CONFIG"]["WRITE"]);
				$adminPermValue = is_array($perms) ? $perms['ATTR'] : $perms;
				if ($adminPermValue >= \Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL)
				{
					continue;
				}
			}
			if (in_array($roleId, $systemRolesIds, false)) // do not affect system roles
			{
				continue;
			}
			$entities[$entityId] = $permissionSet;

			$fields = ["RELATION" => $entities];
			if (!$role->Update($roleId, $fields))
			{
				$result->addError(new Main\Error($fields["RESULT_MESSAGE"]));
			}
		}

		return $result;
	}

	public static function getSystemRolesIds(): array
	{
		return array_column(RoleTable::query()
			->where('IS_SYSTEM', 'Y')
			->setSelect(['ID'])
			->fetchAll(), 'ID');
	}
}

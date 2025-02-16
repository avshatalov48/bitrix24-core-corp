<?php

namespace Bitrix\Crm\Security\Role;

use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Security\Role\Model\RoleTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Result;

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

			if ($permissionType !== (new Transition())->code())
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
	 * @param string $permissionEntityId
	 * @return array it is an array like [roleId => ["READ" => ["-" => "X"], ...]]]
	 */
	public static function getByEntityId(string $permissionEntityId, bool $skipSystemRoles = true)
	{
		$result = [];
		$systemRolesIds = self::getSystemRolesIds();

		$needSplitByRoleGroup = Feature::enabled(\Bitrix\Crm\Feature\PermissionsLayoutV2::class);
		if ($needSplitByRoleGroup)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID((string)Container::getInstance()->getUserPermissions()->getEntityNameByPermissionEntityType($permissionEntityId));
			$strictByRoleGroupCode = (string)\Bitrix\Crm\Security\Role\GroupCodeGenerator::getGroupCodeByEntityTypeId($entityTypeId);
			$rolesIdsInGroup = self::getRolesByGroupCode($strictByRoleGroupCode);
		}

		foreach (self::getAll() as $roleId => $entities)
		{
			if (in_array($roleId, $systemRolesIds, false) && $skipSystemRoles)
			{
				continue;
			}
			if ($needSplitByRoleGroup && !in_array($roleId, $rolesIdsInGroup, false))
			{
				continue;
			}

			if (array_key_exists($permissionEntityId, $entities))
			{
				$result[$roleId] = $entities[$permissionEntityId];
			}
		}

		return $result;
	}

	/**
	 * Sets a permission from the set for certain roles but one entity
	 *
	 * @param string $permissionEntityId
	 * @param array $permissionSet it is an array like [roleId => ["READ" => ["-" => "X"], ...]]]
	 * @param bool $skipAdminRoles Skip roles with "Allow edit config" checkbox to avoid decreasing permissions level in them
	 * @return Main\Result
	 */
	public static function setByEntityId(string $permissionEntityId, array $permissionSet, $skipAdminRoles = false, $skipSystemRoles = true)
	{
		static::$cache = null;
		$systemRolesIds = self::getSystemRolesIds();

		$result = new Main\Result();

		$needSplitByRoleGroup = Feature::enabled(\Bitrix\Crm\Feature\PermissionsLayoutV2::class);
		if ($needSplitByRoleGroup)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID((string)Container::getInstance()->getUserPermissions()->getEntityNameByPermissionEntityType($permissionEntityId));
			$strictByRoleGroupCode = (string)\Bitrix\Crm\Security\Role\GroupCodeGenerator::getGroupCodeByEntityTypeId($entityTypeId);
			$rolesIdsInGroup = self::getRolesByGroupCode($strictByRoleGroupCode);
			$adminRolesIds = self::getAdminRolesIds($entityTypeId);
		}
		else
		{
			$adminRolesIds = self::getAdminRolesIds();
		}

		$role = new \CCrmRole();
		foreach (self::getAll() as $roleId => $entities)
		{
			if (in_array($roleId, $adminRolesIds, false) && $skipAdminRoles) // do not affect admin roles
			{
				continue;
			}
			if (in_array($roleId, $systemRolesIds, false) && $skipSystemRoles) // do not affect system roles
			{
				continue;
			}
			if ($needSplitByRoleGroup && !in_array($roleId, $rolesIdsInGroup, false))
			{
				continue;
			}

			if (array_key_exists($roleId, $permissionSet))
			{
				$entities[$permissionEntityId] = $permissionSet[$roleId];

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
	 * @param CategoryIdentifier $categoryIdentifier
	 * @param array $permissionSet it is an array like ["READ" => ["-" => ["ATTR" => "X"]], ...]]
	 * @return Result
	 */
	public static function setByEntityIdForAllNotAdminRoles(CategoryIdentifier $categoryIdentifier, array $permissionSet, bool $needStrictByRoleGroupCode = true)
	{
		static::$cache = null;

		$permissionEntityId = $categoryIdentifier->getPermissionEntityCode();

		$systemRolesIds = self::getSystemRolesIds();

		if ($needStrictByRoleGroupCode && !Feature::enabled(\Bitrix\Crm\Feature\PermissionsLayoutV2::class))
		{
			$needStrictByRoleGroupCode = false;
		}

		$emptyRoles = [];
		if ($needStrictByRoleGroupCode)
		{
			$strictByRoleGroupCode = (string)\Bitrix\Crm\Security\Role\GroupCodeGenerator::getGroupCodeByEntityTypeId($categoryIdentifier->getEntityTypeId());
			$rolesIdsInGroup = self::getRolesByGroupCode($strictByRoleGroupCode);
			foreach ($rolesIdsInGroup as $roleId)
			{
				$emptyRoles[$roleId] = $roleId;
			}
		}

		$result = new Main\Result();

		if (Feature::enabled(\Bitrix\Crm\Feature\PermissionsLayoutV2::class))
		{
			$adminRolesIds = self::getAdminRolesIds($categoryIdentifier->getEntityTypeId());
		}
		else
		{
			$adminRolesIds = self::getAdminRolesIds();
		}

		$role = new \CCrmRole();

		foreach (self::getAll() as $roleId => $entities)
		{
			if (in_array($roleId, $adminRolesIds, false)) // do not affect admin roles
			{
				continue;
			}
			if (in_array($roleId, $systemRolesIds, false)) // do not affect system roles
			{
				continue;
			}
			if ($needStrictByRoleGroupCode && !in_array($roleId, $rolesIdsInGroup, false))
			{
				continue;
			}
			unset($emptyRoles[$roleId]);

			$entities[$permissionEntityId] = $permissionSet;

			$fields = ["RELATION" => $entities];
			if (!$role->Update($roleId, $fields))
			{
				$result->addError(new Main\Error($fields["RESULT_MESSAGE"]));
			}
		}

		foreach ($emptyRoles as $emptyRoleId)
		{
			if (in_array($emptyRoleId, $adminRolesIds, false)) // do not affect admin roles
			{
				continue;
			}
			if (in_array($emptyRoleId, $systemRolesIds, false)) // do not affect system roles
			{
				continue;
			}

			$fields = ["RELATION" => [
				$permissionEntityId => $permissionSet,
			]];
			if (!$role->Update($emptyRoleId, $fields))
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

	public static function getAdminRolesIds(?int $entityTypeId = null): array
	{
		$result = [];
		$adminRolePermissionName = 'CONFIG';
		if ($entityTypeId && \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$type = Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
			if ($type?->getCustomSectionId())
			{
				$adminRolePermissionName = \Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionConfig::generateEntity($type->getCustomSectionId());
			}
		}

		foreach (self::getAll() as $roleId => $entities)
		{
			if (array_key_exists($adminRolePermissionName, $entities)
				&& array_key_exists("WRITE", $entities[$adminRolePermissionName])
			)
			{
				$perms = reset($entities[$adminRolePermissionName]["WRITE"]);
				$adminPermValue = is_array($perms) ? $perms['ATTR'] : $perms;
				if ($adminPermValue >= \Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL)
				{
					$result[] = $roleId;
				}
			}
		}

		return $result;
	}

	private static function getRolesByGroupCode(string $strictByRoleGroup): array
	{
		$ct = new ConditionTree();
		$ct->where('GROUP_CODE', $strictByRoleGroup);

		if ($strictByRoleGroup === '')
		{
			$ct
				->logic(ConditionTree::LOGIC_OR)
				->whereNull('GROUP_CODE')
			;
		}

		return array_column(RoleTable::query()
			->where($ct)
			->setSelect(['ID'])
			->fetchAll(), 'ID');
	}
}

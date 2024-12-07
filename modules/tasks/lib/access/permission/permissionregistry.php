<?php

namespace Bitrix\Tasks\Access\Permission;

final class PermissionRegistry
{
	private static $instance;
	private static $permissions = [];

	/**
	 * @return self
	 */
	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param array $roles
	 * @return array
	 */
	public function getPermissions(array $roles = []): array
	{
		$permissions = [];

		if (empty(self::$permissions))
		{
			return $permissions;
		}

		foreach (self::$permissions as $roleId => $rolePermissions)
		{
			if (
				!in_array($roleId, $roles)
				|| !is_array($rolePermissions)
				|| !array_key_exists(PermissionDictionary::VALUE_YES, $rolePermissions)
				|| !is_array($rolePermissions[PermissionDictionary::VALUE_YES])
			)
			{
				continue;
			}

			$permissions = array_merge($permissions, $rolePermissions[PermissionDictionary::VALUE_YES]);
		}

		return array_unique($permissions);
	}

	private function __construct()
	{
		$this->loadPermissions();
	}

	private function loadPermissions()
	{
		$res = TasksPermissionTable::query()
			->addSelect("PERMISSION_ID")
			->addSelect("VALUE")
			->addSelect("ROLE_ID")
			->fetchAll();

		foreach ($res as $row)
		{
			$permissionId = (int) $row['PERMISSION_ID'];
			$roleId = (int) $row['ROLE_ID'];
			$value = (int) $row['VALUE'];

			self::$permissions[$roleId][$value][] = $permissionId;
		}
	}
}
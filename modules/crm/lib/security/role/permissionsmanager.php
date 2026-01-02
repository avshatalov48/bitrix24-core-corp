<?php

namespace Bitrix\Crm\Security\Role;

use Bitrix\Crm\Security\Role\Model\RoleRelationTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;

/**
 * @internal
 * Do not use directly. Use \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions() to check permissions
 */
class PermissionsManager
{
	private const CACHE_TIME = 8640000; // 100 days
	private const CACHE_PATH = '/crm/user_permission_roles/';

	private ?array $userRoles = null;

	private array $loadedEntities = [
		'byEntity' => [],
		'byPermission' => [],
		'data' => [],
	];

	private static array $instances = [];

	public static function getInstance(int $userId): self
	{
		if (!isset(self::$instances[$userId]))
		{
			self::$instances[$userId] = new self($userId);
		}

		return self::$instances[$userId];
	}

	private function __construct(
		private readonly int $userId,
	)
	{
	}

	public function hasPermission(string $entity, string $permissionType): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}

		return $this->load($entity, $permissionType)->hasPermission();
	}

	public function hasPermissionLevel(string $entity, string $permissionType, string $level): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}

		return $this->load($entity, $permissionType)->hasPermissionLevel($level);
	}

	public function hasMaxPermissionLevel(string $entity, string $permissionType): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}

		return $this->load($entity, $permissionType)->hasMaxPermissionLevel();
	}

	public function hasPermissionByEntityAttributes(string $entity, string $permissionType, array $entityAttributes): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}

		return $this->load($entity, $permissionType)->hasPermissionByEntityAttributes($entityAttributes);
	}

	/**
	 * @deprecated
	 * Used in backward compatibility methods only
	 * Will be removed soon!
	 */
	public function getPermissionAttributeByEntityAttributes(string $entity, string $permissionType, array $entityAttributes): string
	{
		return $this->load($entity, $permissionType)->getPermissionAttributeByEntityAttributes($entityAttributes);
	}

	public function doUserAttributesMatchesToEntityAttributes(string $entity, string $permissionType, mixed $entityAttributes): bool
	{
		return $this->load($entity, $permissionType)->compareUserAttributesWithEntityAttributes($entityAttributes);
	}

	public function getPermissionLevel(string $entity, string $permissionType): PermissionLevel
	{
		return $this->load($entity, $permissionType);
	}

	private function load(string $entity, string $permissionType): PermissionLevel
	{
		$resultKey = $entity . '_' . $permissionType;

		if (
			isset($this->loadedEntities['byEntity'][$entity])
			|| isset($this->loadedEntities['byPermission'][$permissionType])
		)
		{
			return $this->loadedEntities['data'][$resultKey] ?? $this->createPermissionLevel($entity, $permissionType);
		}
		$roleIds = $this->getUserRoles();

		$filter = [];

		if ($permissionType === UserPermissions::OPERATION_READ) // Read permissions should be loaded all together for optimization
		{
			$filter['=PERM_TYPE'] = $permissionType;
			$this->loadedEntities['byPermission'][$permissionType] = true;
		}
		else
		{
			$filter['=ENTITY'] = $entity;
			$this->loadedEntities['byEntity'][$entity] = true;
		}

		if (empty($roleIds))
		{
			return $this->createPermissionLevel($entity, $permissionType);
		}

		$filter['@ROLE_ID'] = $roleIds;

		$rolePermissions = \Bitrix\Crm\Security\Role\Model\RolePermissionTable::getList([
			'filter' => $filter,
			'select' => [
				'ROLE_ID',
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

		while ($rolePermission = $rolePermissions->fetchObject())
		{
			$roleId = (int)$rolePermission->getRoleId();
			$attribute = (string)$rolePermission->getAttr();
			$field = (string)$rolePermission->getField();
			$fieldValue = (string)$rolePermission->getFieldValue();
			$entity = (string)$rolePermission->getEntity();
			$permType = (string)$rolePermission->getPermType();
			$settings = $rolePermission->getSettings() ?? [];

			$permissionKey = $entity . '_' . $permType;

			$permissionLevel = $this->loadedEntities['data'][$permissionKey] ?? $this->createPermissionLevel($entity, $permType);
			$permissionLevel
				->addValueAttribute($attribute, $roleId, $field, $fieldValue)
				->addValueSettings($settings, $roleId, $field, $fieldValue)
			;
			$this->loadedEntities['data'][$permissionKey] = $permissionLevel;
		}

		return $this->loadedEntities['data'][$resultKey] ?? $this->createPermissionLevel($entity, $permissionType);
	}

	public function getUserRoles(): array
	{
		if (is_array($this->userRoles))
		{
			return $this->userRoles;
		}

		$this->userRoles = [];

		if($this->userId <= 0)
		{
			$this->userRoles = [];

			return $this->userRoles;
		}

		$userAccessCodes = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($this->userId)
			->getAttributesProvider()
			->getUserAttributesCodes()
		;

		if (!empty($userAccessCodes))
		{
			$rolesRelations = RoleRelationTable::getList([
				'filter' => [
					'@RELATION' => $userAccessCodes,
				],
				'select' => [
					'ROLE_ID'
				],
				'cache' => ['ttl' => self::CACHE_TIME]
			]);
			while ($roleRelation = $rolesRelations->fetch())
			{
				$this->userRoles[] = $roleRelation['ROLE_ID'];
			}
		}

		return $this->userRoles;
	}

	private function createPermissionLevel(string $permissionEntity, string $permissionType): PermissionLevel
	{
		return new PermissionLevel(
			$this->userId,
			$permissionEntity,
			$permissionType,
			$this->getUserPermissions()->getAttributesProvider(),
			$this->isAdmin()
		);
	}

	private function isAdmin(): bool
	{
		return $this->getUserPermissions()->isAdmin();
	}

	private function getUserPermissions(): UserPermissions
	{
		return Container::getInstance()->getUserPermissions($this->userId);
	}
}

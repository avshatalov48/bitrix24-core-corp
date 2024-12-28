<?php
namespace Bitrix\Sign\Access\Service;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\Model\UserModel;
use Bitrix\Sign\Access\Permission\PermissionTable;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\SectionDictionary;
use CCrmPerms;

Loc::loadMessages(__FILE__);

Loader::includeModule('crm');

class RolePermissionService extends \Bitrix\Crm\Integration\Sign\Access\Service\RolePermissionService
{
	public const DEFAULT_ROLE_EMPLOYEE_CODE = 'SIGN_SMART_DOCUMENT_EMPLOYMENT';
	public const DEFAULT_ROLE_CHIEF_CODE = 'SIGN_SMART_DOCUMENT_CHIEF';
	public const DEFAULT_ROLES_CODE = [
		self::DEFAULT_ROLE_EMPLOYEE_CODE,
		self::DEFAULT_ROLE_CHIEF_CODE,
	];

	/**
	 * @param list<array{id: string, title: string, accessRights: list<array{id: string, value: string|int}>}> $permissionSettings permission settings array
	 */
	public function saveRolePermissions(array &$permissionSettings): void
	{
		foreach ($permissionSettings as &$setting)
		{
			foreach ($setting['accessRights'] ?? [] as $accessRight)
			{
				if ($accessRight['id'] === PermissionDictionary::SIGN_CRM_CONTACT_READ)
				{
					$setting['accessRights'][] = ['id' => PermissionDictionary::SIGN_CRM_CONTACT_ADD, 'value' => $accessRight['value'],];
					$setting['accessRights'][] = ['id' => PermissionDictionary::SIGN_CRM_CONTACT_WRITE, 'value' => $accessRight['value'],];
					$setting['accessRights'][] = ['id' => PermissionDictionary::SIGN_CRM_CONTACT_DELETE, 'value' => $accessRight['value'],];
					$setting['accessRights'][] = ['id' => PermissionDictionary::SIGN_CRM_CONTACT_IMPORT, 'value' => $accessRight['value'],];
					$setting['accessRights'][] = ['id' => PermissionDictionary::SIGN_CRM_CONTACT_EXPORT, 'value' => $accessRight['value'],];
				}
			}
			$setting['signAccessRights'] = $setting['accessRights'] ?? [];
		}

		parent::saveRolePermissions($permissionSettings);
		$this->updateSignPermissions($permissionSettings);
	}

	/**
	 * @param int $roleId role identification number
	 */
	public function deleteRole(int $roleId): Result
	{
		$deleteResult = parent::deleteRole($roleId);
		if ($deleteResult->isSuccess())
		{
			PermissionTable::deleteList(['=ROLE_ID' => $roleId]);
		}
		
		return $deleteResult;
	}
	
	/**
	 * @return array
	 */
	public function getUserGroups(): array
	{
		$res = $this->getRoleList();

		$roles = [];
		foreach ($res as $row)
		{
			$roleId = (int) $row['ID'];

			$roles[] = [
				'id' => $roleId,
				'title' => $row['NAME'],
				'accessRights' => $this->getRoleAccessRights($roleId),
				'members' => $this->getRoleMembers($roleId),
			];
		}

		return $roles;
	}

	/**
	 * returns access rights list
	 * @return array
	 */
	public function getAccessRights(): array
	{
		$sections = SectionDictionary::getMap();

		$res = [];

		foreach ($sections as $sectionId => $permissions)
		{

			$rights = [];
			foreach ($permissions as $permissionId)
			{
				$type = PermissionDictionary::getType($permissionId)
					?: SignPermissionDictionary::getType($permissionId);
				$title = PermissionDictionary::getTitle($permissionId)
					?: SignPermissionDictionary::getTitle($permissionId);
				
				$rights[] = [
					'id' => $permissionId,
					'type' => $type,
					'title' => $title,
					'enableSearch' => true,
					'variables' => $this->getAbleOptions(!is_int($permissionId)),
				];
			}
			
			$res[] = [
				'sectionTitle' => SectionDictionary::getTitle($sectionId),
				'rights' => $rights,
			];
		}

		return $res;
	}

	protected function getAbleOptions(bool $showOpen = true): array
	{
		return array_merge([
			[
				'id' => 0,
				'title' => Loc::getMessage('SIGN_ACCESS_ROLE_NONE'),
			],
			[
				'id' => CCrmPerms::PERM_ALL,
				'title' => Loc::getMessage('SIGN_ACCESS_ROLE_ALL'),
			],
			[
				'id' => CCrmPerms::PERM_SELF,
				'title' => Loc::getMessage('SIGN_ACCESS_ROLE_SELF'),
			],
			[
				'id' => CCrmPerms::PERM_DEPARTMENT,
				'title' => Loc::getMessage('SIGN_ACCESS_ROLE_SELF_DEPARTMENT'),
			],
			[
				'id' => CCrmPerms::PERM_SUBDEPARTMENT,
				'title' => Loc::getMessage('SIGN_ACCESS_ROLE_SELF_DEPARTMENT_ALL'),
			],
		],$showOpen ? [
			[
				'id' => CCrmPerms::PERM_OPEN,
				'title' => Loc::getMessage('SIGN_ACCESS_ROLE_OPENED'),
			],
		] : []);
	}

	/**
	 * @return array<int, array<string|int, array{VALUE: string|int}>>
	 */
	public function getSettings(): array
	{
		if (!empty(static::$settings))
		{
			return static::$settings;
		}
		$res = $this->getRoleList();
		
		$roles = [];
		foreach ($res as $row)
		{
			$roles[] = (int)$row['ID'];
		}

		$permissions = $this->getSavedPermissions($roles);
		static::$settings = $this->mapCurrentPermissions($permissions);

		return static::$settings;
	}

	protected function mapCurrentPermissions($permissions): array
	{
		$preparedPermissions = parent::mapCurrentPermissions($permissions);
		try
		{
			$signPermissions = PermissionTable::getList()->fetchAll();
		}
		catch (ObjectPropertyException|ArgumentException|SystemException $e)
		{
			return $preparedPermissions;
		}

		foreach ($signPermissions as $signPermission)
		{
			$preparedPermissions[$signPermission['ROLE_ID']][$signPermission['PERMISSION_ID']] = [
				'VALUE' => $signPermission['VALUE'],
			];
		}
		
		return $preparedPermissions;
	}

	private function updateSignPermissions($permissionSettings)
	{
		foreach ($permissionSettings as $setting)
		{
			$roleId = (int)$setting['id'];
			PermissionTable::deleteList(['=ROLE_ID' => $roleId]);

			foreach ($setting['signAccessRights'] as $permission)
			{
				if (
					!$permission['id']
					|| !$permission['value']
					|| !$roleId
				)
				{
					continue;
				}

				$permission['id'] = (int)$permission['id'];

				if ($permission['id'] > 0)
				{
					PermissionTable::add([
						'ROLE_ID' => $roleId,
						'PERMISSION_ID' => $permission['id'],
						'VALUE' => $permission['value'],
					]);
				}
			}
		}
	}

	public function isAllSignPermissionsEmpty(): bool
	{
		if (!Loader::includeModule('crm'))
		{
			return false;
		}

		$signPermissions = PermissionTable::query()
			->setSelect(['ID'])
			->whereNotIn('VALUE', ['0', \CCrmPerms::PERM_NONE])
			->setLimit(1)
			->fetchAll()
		;

		return empty($signPermissions);
	}

	protected function getLocalPermissionList(): array
	{
		return PermissionDictionary::getList();
	}
	
	protected function getPermissionCode(string $id): ?string
	{
		return PermissionDictionary::getName($id);
	}

	public function getValueForPermission(array $roles, string $permissionId): ?string
	{
		if (empty($roles))
		{
			return null;
		}

		try
		{
			$permissions = PermissionTable::query()
				->setSelect(['VALUE'])
				->whereIn('ROLE_ID', $roles)
				->where('PERMISSION_ID', $permissionId)
				->exec()
				->fetchAll()
			;

			$isValueFound = false;
			$max = '';
			foreach ($permissions as $permission)
			{
				if ($permission['VALUE'] > $max)
				{
					$isValueFound = true;
					$max = $permission['VALUE'];
				}
			}

			return !$isValueFound ? null : $max;
		}
		catch (ObjectPropertyException|ArgumentException|SystemException $e)
		{
			return null;
		}
	}

	/**
	 * @return array<int, array{CODE: string} role id to its code
	 */
	public function getDefaultRoles(): array
	{
		$defaultRoleIds = [];
		foreach ($this->getRoleList() as $role)
		{
			if (in_array($role['CODE'], self::DEFAULT_ROLES_CODE, true))
			{
				$defaultRoleIds[(int)$role['ID']] = ['CODE' => $role['CODE']];
			}
		}

		return $defaultRoleIds;
	}

	/**
	 * @return list<array{id: string|int, value: string, type: string, enableSearch: bool}> its not return sign permissions if its empty
	 */
	public function getFlatAccessRightsFromAllRoles(): array
	{
		$userGroups = $this->getUserGroups();
		$accessRights = array_column($userGroups, 'accessRights');
		$flatAccessRightsFromAllRoles = [];
		foreach ($accessRights as $accessRight)
		{
			$flatAccessRightsFromAllRoles = array_merge($flatAccessRightsFromAllRoles, $accessRight);
		}

		return $flatAccessRightsFromAllRoles;
	}
}
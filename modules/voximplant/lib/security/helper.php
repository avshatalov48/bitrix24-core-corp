<?php

namespace Bitrix\Voximplant\Security;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Model;
use Bitrix\Voximplant\PhoneTable;

Loc::loadMessages(__FILE__);

class Helper
{
	/**
	 * @param int $userId
	 * @param string $permission
	 * @return array|null Returns array of owner user ids if there is limit and null if query should not be limited.
	 */
	public static function getAllowedUserIds($userId, $permission)
	{
		$result = [];
		switch ($permission)
		{
			case Permissions::PERMISSION_NONE:
				$result = [];
				break;
			case Permissions::PERMISSION_SELF:
				$result = [$userId];
				break;
			case Permissions::PERMISSION_DEPARTMENT:
				$result = self::getUserColleagues($userId);
				break;
			case Permissions::PERMISSION_ANY:
				$result = null;
				break;
		}

		return $result;
	}

	/**
	 * @return int
	 */
	public static function getCurrentUserId()
	{
		return ($GLOBALS['USER'] instanceof \CUser) ?  (int)$GLOBALS['USER']->GetID() : 0;
	}

	/**
	 * Returns true if user is portal admin.
	 *
	 * @param int $userId Id of the user.
	 * @return bool
	 */
	public static function isAdmin($userId = null): bool
	{
		global $USER;

		if(!($USER instanceof \CUser))
		{
			return false;
		}

		if(!$userId || $userId == $USER->getId())
		{
			if(Loader::includeModule('bitrix24'))
			{
				return $USER->CanDoOperation('bitrix24_config');
			}
			else
			{
				return $USER->IsAdmin();
			}
		}

		if(Loader::includeModule('bitrix24'))
		{
			// Bitrix24 context new style check
			return \CBitrix24::IsPortalAdmin($userId);
		}
		else
		{
			//Check user group 1 ('Admins')
			$user = new \CUser();
			$userGroups = $user::getUserGroup($userId);

			return in_array(1, $userGroups);
		}
	}

	/**
	 * @param $userId
	 * @return array
	 */
	public static function getUserColleagues(int $userId): array
	{
		$structureService = \Bitrix\Voximplant\Integration\HumanResources\StructureService::getInstance();
		return $structureService->getUserColleagues($userId);
	}

	public static function isMainMenuEnabled(): bool
	{
		return (
			self::isBalanceMenuEnabled() ||
			self::isSettingsMenuEnabled() ||
			self::isLinesMenuEnabled() ||
			self::isUsersMenuEnabled()
		);
	}

	public static function isBalanceMenuEnabled(): bool
	{
		$permissions = Permissions::createWithCurrentUser();
		return (
			$permissions->canPerform(Permissions::ENTITY_CALL_DETAIL, Permissions::ACTION_VIEW)
			|| $permissions->canPerform(Permissions::ENTITY_LINE, Permissions::ACTION_MODIFY)
			|| $permissions->canPerform(Permissions::ENTITY_BALANCE, Permissions::ACTION_MODIFY)
		);
	}

	public static function isSettingsMenuEnabled(): bool
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canModifySettings();
	}

	public static function isLinesMenuEnabled(): bool
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canModifyLines();
	}

	public static function isUsersMenuEnabled(): bool
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canPerform(Permissions::ENTITY_USER, Permissions::ACTION_MODIFY);
	}

	public static function clearMenuCache(): void
	{
		\Bitrix\Main\Application::getInstance()->getTaggedCache()->clearByTag('bitrix:menu');
	}

	public static function canCurrentUserPerformCalls(): bool
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canPerform(Permissions::ENTITY_CALL, Permissions::ACTION_PERFORM);
	}

	public static function canCurrentUserCallFromCrm(): bool
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canPerform(Permissions::ENTITY_CALL, Permissions::ACTION_PERFORM, Permissions::PERMISSION_CALL_CRM);
	}

	public static function canCurrentUserPerformAnyCall(): bool
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canPerform(Permissions::ENTITY_CALL, Permissions::ACTION_PERFORM, Permissions::PERMISSION_ANY);
	}

	public static function canUserCallNumber($userId, $number, $country = ''): bool
	{
		$result = false;
		$userPermissions = Permissions::createWithUserId($userId);
		$callPermission = $userPermissions->getPermission(Permissions::ENTITY_CALL, Permissions::ACTION_PERFORM);

		switch ($callPermission)
		{
			case Permissions::PERMISSION_NONE:
				$result = false;
				break;
			case Permissions::PERMISSION_ANY:
				$result = true;
				break;
			case Permissions::PERMISSION_CALL_CRM:
				$result = (\CVoxImplantCrmHelper::GetCrmEntity($number, $country) !== false);
				break;
			case Permissions::PERMISSION_CALL_USERS:
				$result = (\CVoxImplantCrmHelper::GetCrmEntity($number, $country) !== false);
				if(!$result)
				{
					$cursor = PhoneTable::getList([
						'filter' => [
							'=PHONE_NUMBER' => \CVoxImplantPhone::Normalize($number),
						]
					]);
					$result = ($cursor->fetch() !== false);					
				}
				break;
		}
		return $result;
	}

	public static function canUpdateBalance(): bool
	{
		if (\Bitrix\Voximplant\Limits::isRestOnly())
		{
			return false;
		}

		if (self::isAdmin())
		{
			return true;
		}

		if (!self::canUse())
		{
			return false;
		}

		return
			Permissions::createWithCurrentUser()
				->canPerform(Permissions::ENTITY_BALANCE, Permissions::ACTION_MODIFY)
		;
	}

	public static function canUse(): bool
	{
		if(!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return Feature::isFeatureEnabled('voximplant_security');
	}

	/**
	 * Deletes oll roles and permissions and creates default ones instead.
	 * @return null
	 */
	public static function resetToDefault(): void
	{
		Model\RoleTable::truncate();
		Model\RoleAccessTable::truncate();
		Model\RolePermissionTable::truncate();

		static::createDefaultRoles();
	}

	/**
	 * Creates default roles and associates them whith access tokens.
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Exception
	 */
	public static function createDefaultRoles(): bool
	{
		$checkCursor = \Bitrix\Voximplant\Model\RoleTable::getList([
			'limit' => 1
		]);

		if($checkCursor->fetch())
		{
			return false;
		}

		$roleIds = [];
		foreach (static::getDefaultRoles() as $roleCode => $role)
		{
			$addResult = \Bitrix\Voximplant\Model\RoleTable::add([
				'NAME' => $role['NAME'],
			]);

			$roleId = $addResult->getId();
			if($roleId)
			{
				$roleIds[$roleCode] = $roleId;
				RoleManager::setRolePermissions($roleId, $role['PERMISSIONS']);
			}
		}

		foreach (static::getDefaultRoleAccess() as $roleAccess)
		{
			if(isset($roleIds[$roleAccess['ROLE']]))
			{
				Model\RoleAccessTable::add([
					'ROLE_ID' => $roleIds[$roleAccess['ROLE']],
					'ACCESS_CODE' => $roleAccess['ACCESS_CODE']
				]);
			}
		}

		return true;
	}

	public static function getDefaultRoles(): array
	{
		return [
			'admin' => [
				'NAME' => Loc::getMessage('VOXIMPLANT_ROLE_ADMIN'),
				'PERMISSIONS' => [
					'CALL_DETAIL' => [
						'VIEW' => 'X',
					],
					'CALL' => [
						'PERFORM' => 'X'
					],
					'CALL_RECORD' => [
						'LISTEN' => 'X'
					],
					'USER' => [
						'MODIFY' => 'X'
					],
					'SETTINGS' => [
						'MODIFY' => 'X'
					],
					'LINE' => [
						'MODIFY' => 'X'
					]
				]
			],
			'chief' => [
				'NAME' => Loc::getMessage('VOXIMPLANT_ROLE_CHIEF'),
				'PERMISSIONS' => [
					'CALL_DETAIL' => [
						'VIEW' => 'X',
					],
					'CALL' => [
						'PERFORM' => 'X'
					],
					'CALL_RECORD' => [
						'LISTEN' => 'X'
					],
				]
			],
			'department_head' => [
				'NAME' => Loc::getMessage('VOXIMPLANT_ROLE_DEPARTMENT_HEAD'),
				'PERMISSIONS' => [
					'CALL_DETAIL' => [
						'VIEW' => 'D',
					],
					'CALL' => [
						'PERFORM' => 'X'
					],
					'CALL_RECORD' => [
						'LISTEN' => 'D'
					],
				]
			],
			'manager' => [
				'NAME' => Loc::getMessage('VOXIMPLANT_ROLE_MANAGER'),
				'PERMISSIONS' => [
					'CALL_DETAIL' => [
						'VIEW' => 'A',
					],
					'CALL' => [
						'PERFORM' => 'X'
					],
					'CALL_RECORD' => [
						'LISTEN' => 'A'
					],
				]
			]
		];
	}

	public static function getDefaultRoleAccess(): array
	{
		$result = [
				[
				'ROLE' => 'admin',
				'ACCESS_CODE' => 'G1'
			]
		];

		$structureService = \Bitrix\Voximplant\Integration\HumanResources\StructureService::getInstance();
		$rootDepartment = $structureService->getRootDepartmentId();
		if ($rootDepartment > 0)
		{
			$result[] = [
				'ROLE' => 'manager',
				'ACCESS_CODE' => 'DR'.$rootDepartment
			];
		}

		return $result;
	}
}
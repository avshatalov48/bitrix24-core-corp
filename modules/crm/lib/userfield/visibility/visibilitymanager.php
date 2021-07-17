<?php

namespace Bitrix\Crm\UserField\Visibility;

use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\UserField\Access\ActionDictionary;
use Bitrix\Main\UserField\Access\Permission\UserFieldPermissionTable;
use Bitrix\Main\UserField\Access\UserFieldAccessController;
use Bitrix\Main\Localization\Loc;
use CCrmSecurityHelper;
use CSocNetLogDestination;

Loc::loadMessages(__FILE__);

/**
 * Class VisibilityManager
 * @package Bitrix\Crm\UserField\Visibility
 */
class VisibilityManager
{
	private static $isEnabled;

	/**
	 * @return bool
	 */
	public static function isEnabled(): bool
	{
		if (static::$isEnabled === null)
		{
			static::$isEnabled = RestrictionManager::getAttributeConfigRestriction()->hasPermission();
		}
		return static::$isEnabled;
	}

	/**
	 * @param array|bool $accessCodes
	 * @param string $fieldName
	 * @param int $entityTypeId
	 * @param string $permissionId
	 */
	public static function saveEntityConfiguration($accessCodes, string $fieldName, int $entityTypeId, string $permissionId): void
	{
		UserFieldPermissionTable::saveEntityConfiguration(
			$accessCodes,
			$fieldName,
			$entityTypeId,
			$permissionId,
			\CCrmOwnerType::ResolveUserFieldEntityID($entityTypeId)
		);
	}

	/**
	 * @param int $entityTypeId
	 * @param array|null $userAccessCodes
	 * @return array
	 */
	public static function getNotAccessibleFields(int $entityTypeId, ?array $userAccessCodes = null): array
	{
		$accessCodes = static::getUserFieldsAccessCodes($entityTypeId);
		if ($userAccessCodes === null)
		{
			$userAccessCodes = self::getUserAccessCodes();
		}

		$excludedFields = [];
		foreach ($accessCodes as $name => $item)
		{
			if (isset($item['accessCodes']) && !empty($name))
			{
				$fieldAccessCodes = array_keys($item['accessCodes']);
				if (!count(array_intersect($userAccessCodes, $fieldAccessCodes)))
				{
					$excludedFields[] = $name;
				}
			}
		}

		return $excludedFields;
	}

	/**
	 * @param int $entityTypeId
	 * @param array $fieldNames
	 * @param array|null $userAccessCodes
	 * @return array
	 */
	public static function filterNotAccessibleFields(
		int $entityTypeId,
		array $fieldNames,
		?array $userAccessCodes = null
	): array
	{
		if(empty($fieldNames))
		{
			return $fieldNames;
		}
		$notAccessibleFields = static::getNotAccessibleFields($entityTypeId, $userAccessCodes);

		return array_diff($fieldNames, $notAccessibleFields);
	}

	/**
	 * @return array
	 */
	private static function getUserAccessCodes(): array
	{
		$user = \CCrmSecurityHelper::getCurrentUser();
		return $user->getAccessCodes();
	}

	/**
	 * @param int $entityTypeID lead=1|deal=2|company=3|contact=4|etc, see CCrmOwnerType
	 * @return array
	 */
	public static function getUserFieldsAccessCodes(int $entityTypeID): array
	{
		if (!\CCrmOwnerType::IsEntity($entityTypeID))
		{
			throw new \Bitrix\Main\ArgumentException('Entity type id is not valid');
		}

		$fields = UserFieldPermissionTable::getUserFieldsAccessCodes($entityTypeID);
		$usersInfo = self::getUsersInfo($fields);

		return self::prepareUserFieldsAccessCodes($fields, $usersInfo);
	}

	/**
	 * @param array $fields
	 * @param array $usersInfo
	 * @return array
	 */
	private static function prepareUserFieldsAccessCodes(array $fields, array $usersInfo): array
	{
		$results = [];

		foreach ($fields as $field)
		{
			$fieldName = $field['FIELD_NAME'];
			$accessCode = $field['ACCESS_CODE'];
			if (isset($usersInfo[$accessCode]))
			{
				$results[$fieldName]['accessCodes'][$accessCode] = $usersInfo[$accessCode];
			}
		}

		return $results;
	}


	/**
	 * @param array $rows
	 * @return array
	 */
	private static function getUsersInfo(array $rows): array
	{
		$ids = [];
		foreach ($rows as $fields)
		{
			$ids[] = $fields['USER_ID'];
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			throw new Main\ObjectNotFoundException('Socialnetwork module is not installed');
		}

		$users = CSocNetLogDestination::getUsers(['id' => array_unique($ids)]);

		return array_map(static function ($user)
		{
			unset($user['email'], $user['checksum']);
			return $user;
		}, $users);
	}

	/**
	 * @param array $userFields
	 * @param int $userId
	 * @return array
	 */
	public static function getVisibleUserFields(array $userFields, ?int $userId = null): array
	{
		$userFieldIds = array_column($userFields, 'ID');

		if ($userId === null)
		{
			$userId = \CCrmSecurityHelper::getCurrentUserId();
		}

		$accessibleFields = UserFieldAccessController::getAccessibleFields(
			$userId,
			ActionDictionary::ACTION_USER_FIELD_VIEW,
			$userFieldIds
		);

		$result = [];
		foreach ($userFields as $fieldName => $userField)
		{
			if(
				!array_key_exists($userField['ID'], $accessibleFields)
				|| $accessibleFields[$userField['ID']]
			)
			{
				$result[$fieldName] = $userField;
			}
		}

		return $result;
	}
}

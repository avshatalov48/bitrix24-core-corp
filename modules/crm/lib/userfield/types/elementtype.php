<?php

namespace Bitrix\Crm\UserField\Types;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\Types\StringType;
use CCrmPerms;
use CUserTypeManager;

Loc::loadMessages(__FILE__);

/**
 * Class ElementType
 * @package Bitrix\Crm\UserField\Types
 */
class ElementType extends StringType
{
	public const
		USER_TYPE_ID = 'crm',
		RENDER_COMPONENT = 'bitrix:crm.field.element';

	protected const ENTITY_TYPE_NAMES = [
		'D' => 'DEAL',
		'C' => 'CONTACT',
		'CO' => 'COMPANY',
		'O' => 'ORDER',
		'L' => 'LEAD'
	];

	protected const ENTITY_TYPE_NAME_DEFAULT = 'L';

	public static function getDescription(): array
	{
		return [
			'DESCRIPTION' => Loc::getMessage('USER_TYPE_CRM_DESCRIPTION'),
			'BASE_TYPE' => CUserTypeManager::BASE_TYPE_STRING,
		];
	}

	public static function prepareSettings(array $userField): array
	{
		$entityType['LEAD'] =
			($userField['SETTINGS']['LEAD'] === 'Y' ? 'Y' : 'N');
		$entityType['CONTACT'] =
			($userField['SETTINGS']['CONTACT'] === 'Y' ? 'Y' : 'N');
		$entityType['COMPANY'] =
			($userField['SETTINGS']['COMPANY'] === 'Y' ? 'Y' : 'N');
		$entityType['DEAL'] =
			($userField['SETTINGS']['DEAL'] === 'Y' ? 'Y' : 'N');
		$entityType['ORDER'] =
			($userField['SETTINGS']['ORDER'] === 'Y' ? 'Y' : 'N');

		$entityQuantity = 0;

		foreach($entityType as $result)
		{
			if($result === 'Y')
			{
				$entityQuantity++;
			}
		}

		$entityType['LEAD'] = ($entityQuantity === 0) ? 'Y' : $entityType['LEAD'];

		return [
			'LEAD' => $entityType['LEAD'],
			'CONTACT' => $entityType['CONTACT'],
			'COMPANY' => $entityType['COMPANY'],
			'DEAL' => $entityType['DEAL'],
			'ORDER' => $entityType['ORDER'],
		];
	}

	/**
	 * @param array $userField
	 * @param array|string $value
	 * @return array
	 */
	public static function checkFields(array $userField, $value): array
	{
		return [];
	}

	/**
	 * @param array $userField
	 * @param bool|int $userId
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function checkPermission(array $userField, $userId = false): bool
	{
		//permission check is disabled
		if(!$userId)
		{
			return true;
		}

		if(!Loader::includeModule('crm'))
		{
			return false;
		}

		$userPerms = (
		$userId > 0 ?
			CCrmPerms::GetUserPermissions($userId) : CCrmPerms::GetCurrentUserPermissions()
		);

		return CCrmPerms::IsAccessEnabled($userPerms);
	}

	/**
	 * @param string $type
	 * @return string
	 */
	public static function getShortEntityType(string $type): string
	{
		$entityTypeNames = array_flip(self::ENTITY_TYPE_NAMES);

		return ($entityTypeNames[$type] ??
			$entityTypeNames[self::ENTITY_TYPE_NAME_DEFAULT]
		);
	}

	/**
	 * @param string $type
	 * @return string
	 */
	public static function getLongEntityType(string $type): string
	{
		return (self::ENTITY_TYPE_NAMES[$type] ??
			self::ENTITY_TYPE_NAMES[self::ENTITY_TYPE_NAME_DEFAULT]
		);
	}

	/**
	 * @return array
	 */
	public static function getEntityTypeNames(): array
	{
		return static::ENTITY_TYPE_NAMES;
	}
}
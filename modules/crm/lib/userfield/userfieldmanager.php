<?php
namespace Bitrix\Crm\UserField;

use Bitrix\Crm;
use Bitrix\Main;

class UserFieldManager
{
	/** @var \CCrmFields[]|null */
	private static $userFieldEntities;
	protected static $linkedUserFields;

	public static function resolveUserFieldEntityID($entityTypeID)
	{
		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return \CCrmLead::GetUserFieldEntityID();
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			return \CCrmDeal::GetUserFieldEntityID();
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return \CCrmContact::GetUserFieldEntityID();
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return \CCrmCompany::GetUserFieldEntityID();
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			return \CCrmQuote::GetUserFieldEntityID();
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			return \CCrmInvoice::GetUserFieldEntityID();
		}

		return '';
	}
	public static function resolveEntityTypeID($userFieldEntityID)
	{
		if($userFieldEntityID === \CCrmLead::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Lead;
		}
		elseif($userFieldEntityID === \CCrmDeal::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Deal;
		}
		elseif($userFieldEntityID === \CCrmContact::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Contact;
		}
		elseif($userFieldEntityID === \CCrmCompany::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Company;
		}
		elseif($userFieldEntityID === \CCrmQuote::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Quote;
		}
		elseif($userFieldEntityID === \CCrmInvoice::GetUserFieldEntityID())
		{
			return \CCrmOwnerType::Invoice;
		}

		return \CCrmOwnerType::Undefined;
	}
	public static function getUserFieldEntity($entityTypeID)
	{
		global $USER_FIELD_MANAGER;

		$userFieldEntityID = self::resolveUserFieldEntityID($entityTypeID);
		if($userFieldEntityID === '')
		{
			return null;
		}

		if(self::$userFieldEntities === null)
		{
			self::$userFieldEntities = array();
		}

		if(isset(self::$userFieldEntities[$userFieldEntityID]))
		{
			return self::$userFieldEntities[$userFieldEntityID];
		}

		return (self::$userFieldEntities[$userFieldEntityID] = new \CCrmFields($USER_FIELD_MANAGER, $userFieldEntityID));
	}
	public static function prepareUserFieldSignature(array $fieldInfo, $value = null)
	{
		$signatureParams = array();

		if(isset($fieldInfo['ENTITY_ID']))
		{
			$signatureParams['ENTITY_ID'] = $fieldInfo['ENTITY_ID'];
		}

		if(isset($fieldInfo['FIELD']))
		{
			$signatureParams['FIELD'] = $fieldInfo['FIELD'];
		}
		elseif(isset($fieldInfo['FIELD_NAME']))
		{
			$signatureParams['FIELD'] = $fieldInfo['FIELD_NAME'];
		}

		if($value !== null)
		{
			$signatureParams['VALUE'] = $value;
		}
		elseif(isset($fieldInfo['VALUE']))
		{
			$signatureParams['VALUE'] = $fieldInfo['VALUE'];
		}

		return Main\UserField\Dispatcher::instance()->getSignature($signatureParams);
	}

	/**
	 * Return array of descriptions about linked to crm user fields in other modules.
	 *
	 * @return array[]
	 */
	public static function getLinkedUserFieldsDescription(): array
	{
		return [
			static::combineUserFieldFieldsToString(
				Crm\Integration\Calendar::USER_FIELD_ENTITY_ID,
				Crm\Integration\Calendar::EVENT_FIELD_NAME
			) => [
				'moduleId' => 'calendar',
				'title' => Main\Localization\Loc::getMessage('CRM_USER_FIELD_MANAGER_FIELD_UF_CRM_CAL_EVENT'),
			],
			static::combineUserFieldFieldsToString(
				Crm\Integration\TaskManager::TASK_USER_FIELD_ENTITY_ID,
				Crm\Integration\TaskManager::TASK_FIELD_NAME
			) => [
				'moduleId' => 'tasks',
				'title' => Main\Localization\Loc::getMessage('CRM_USER_FIELD_MANAGER_FIELD_UF_CRM_TASK'),
			],
			static::combineUserFieldFieldsToString(
				Crm\Integration\TaskManager::TASK_TEMPLATE_USER_FIELD_ENTITY_ID,
				Crm\Integration\TaskManager::TASK_FIELD_NAME
			) => [
				'moduleId' => 'tasks',
				'title' => Main\Localization\Loc::getMessage('CRM_USER_FIELD_MANAGER_FIELD_UF_CRM_TASK_TEMPLATE'),
			],
		];
	}

	/**
	 * Combine entityId and fieldName into a unique string.
	 *
	 * @param string $entityId
	 * @param string $fieldName
	 * @return string
	 */
	public static function combineUserFieldFieldsToString(string $entityId, string $fieldName): string
	{
		return $entityId . '|' . $fieldName;
	}

	/**
	 * Return full info about user fields from UserFieldTable by their description.
	 *
	 * @param array $descriptions
	 *
	 * @return array[]
	 */
	public static function getLinkedUserFields(array $descriptions): array
	{
		if (static::$linkedUserFields !== null)
		{
			return static::$linkedUserFields;
		}

		$descriptions = array_filter(
			$descriptions,
			static function($description) {
				return (
					isset($description['moduleId'], $description['title'])
					&& Main\Loader::includeModule($description['moduleId'])
				);
			}
		);
		if (empty($descriptions))
		{
			return [];
		}
		$filter = [
			'LOGIC' => 'OR',
		];
		foreach ($descriptions as $name => $description)
		{
			$userFieldFields = static::parseUserFieldFieldsFromString($name);
			if ($userFieldFields)
			{
				$filter[] = [
					'=ENTITY_ID' => $userFieldFields['entityId'],
					'=FIELD_NAME' => $userFieldFields['fieldName'],
				];
			}
		}
		if (empty($filter))
		{
			return [];
		}

		static::$linkedUserFields = Main\UserFieldTable::getList([
			'filter' => $filter,
		])->fetchAll();

		return static::$linkedUserFields;
	}

	/**
	 * Return array of user fields that are linked to crm user fields in other modules.
	 *
	 * @return array[] [string combinedUserFieldName => array userField]
	 */
	public static function getLinkedUserFieldsMap(): array
	{
		$description = static::getLinkedUserFieldsDescription();

		$map = [];
		foreach (static::getLinkedUserFields($description) as $linkedUserField)
		{
			$name = static::combineUserFieldFieldsToString(
				$linkedUserField['ENTITY_ID'],
				$linkedUserField['FIELD_NAME']
			);

			$map[$name] = $linkedUserField;
		}

		return $map;
	}

	/**
	 * Return entityId and fieldName of a userField from combined name.
	 *
	 * @param string $combinedFields
	 * @return array|null
	 */
	public static function parseUserFieldFieldsFromString(string $combinedFields): ?array
	{
		/** @var string[] $data */
		$data = explode('|', $combinedFields);
		if (count($data) === 2)
		{
			return [
				'entityId' => $data[0],
				'fieldName' => $data[1],
			];
		}

		return null;
	}

	/**
	 * Return true if this entity is enabled in userField settings.
	 *
	 * @param array $userField
	 * @param string $entityTypeName
	 * @return bool
	 */
	public static function isEntityEnabledInUserField(array $userField, string $entityTypeName): bool
	{
		return (
			isset($userField['SETTINGS'][$entityTypeName])
			&& $userField['SETTINGS'][$entityTypeName] === 'Y'
		);
	}

	/**
	 * Enable or disable entity with name $entityTypeName in settings of a userField.
	 *
	 * @param array $settings
	 * @param string $entityTypeName
	 * @param bool $isEnabled
	 * @return array
	 */
	public static function processUserFieldEntitySettings(array $settings, string $entityTypeName, bool $isEnabled): array
	{
		$settings[$entityTypeName] = $isEnabled ? 'Y' : 'N';

		return $settings;
	}

	/**
	 * Saves new status of entity with name $entityTypeName in settings of $userField in the database.
	 *
	 * @param array $userField
	 * @param string $entityTypeName
	 * @param bool $isEnabled
	 * @return bool
	 */
	public static function enableEntityInUserField(array $userField, string $entityTypeName, bool $isEnabled): bool
	{
		$settings = $userField['SETTINGS'] ?? [];
		$settings = static::processUserFieldEntitySettings($settings, $entityTypeName, $isEnabled);

		$userTypeEntity = new \CUserTypeEntity();

		return $userTypeEntity->Update($userField['ID'], [
			'SETTINGS' => $settings,
		]);
	}

	public static function isEnabledInTasksUserField(string $entityTypeName): bool
	{
		if (!Main\Loader::includeModule('tasks'))
		{
			return false;
		}

		$filter = [
			'=ENTITY_ID' => Crm\Integration\TaskManager::TASK_USER_FIELD_ENTITY_ID,
			'=FIELD_NAME' => Crm\Integration\TaskManager::TASK_FIELD_NAME,
		];

		$userField = Main\UserFieldTable::getList([
			'filter' => $filter,
		])->fetch();
		if (!$userField)
		{
			return false;
		}

		return static::isEntityEnabledInUserField($userField, $entityTypeName);
	}

	public static function isEnabledInCalendarUserField(string $entityTypeName): bool
	{
		if (!Main\Loader::includeModule('calendar'))
		{
			return false;
		}

		$filter = [
			'=ENTITY_ID' => \Bitrix\Crm\Integration\Calendar::USER_FIELD_ENTITY_ID,
			'=FIELD_NAME' => \Bitrix\Crm\Integration\Calendar::EVENT_FIELD_NAME,
		];

		$userField = Main\UserFieldTable::getList([
			'filter' => $filter,
		])->fetch();
		if (!$userField)
		{
			return false;
		}

		return static::isEntityEnabledInUserField($userField, $entityTypeName);
	}
}

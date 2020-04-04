<?php
namespace Bitrix\Crm\Synchronization;

use Bitrix\Main;
use Bitrix\Crm\UserField\UserFieldManager;
use Bitrix\Crm\Conversion\ConversionManager;

class UserFieldLabelSynchronizer extends UserFieldCustomSynchronizer
{
	/** @var array */
	private static $synchronizedFields = array();

	protected static function isFieldLocked($fieldID)
	{
		return isset(self::$synchronizedFields[$fieldID]);
	}
	protected static function lockField($fieldID)
	{
		self::$synchronizedFields[$fieldID] = true;
	}
	protected static function unlockField($fieldID)
	{
		unset(self::$synchronizedFields[$fieldID]);
	}

	public static function synchronize($userFieldEntityType, $fieldName, array $params = null)
	{
		if(!\CCrmAuthorizationHelper::CheckConfigurationUpdatePermission(self::getCurrentUserPermissions()))
		{
			return false;
		}

		$entityTypeID = UserFieldManager::resolveEntityTypeID($userFieldEntityType);
		if($entityTypeID === \CCrmOwnerType::Undefined)
		{
			return false;
		}

		$fieldData = is_array($params) && isset($params['FIELD_DATA']) && is_array($params['FIELD_DATA'])
			? $params['FIELD_DATA'] : array();

		if(empty($fieldData))
		{
			$userFieldEntity = UserFieldManager::getUserFieldEntity($entityTypeID);
			if(!$userFieldEntity)
			{
				return false;
			}

			$field = $userFieldEntity->GetByName($fieldName);
			if(!is_array($field))
			{
				return false;
			}

			$label = isset($field['EDIT_FORM_LABEL'])
				? $field['EDIT_FORM_LABEL'] : (isset($field['LIST_COLUMN_LABEL']) ? $field['LIST_COLUMN_LABEL'] : '');

			if($label === '')
			{
				return false;
			}

			$labels = array_fill_keys(self::getLanguageIDs(), $label);
			$fieldData = array(
				'EDIT_FORM_LABEL' => $labels,
				'LIST_COLUMN_LABEL' => $labels,
				'LIST_FILTER_LABEL' => $labels
			);
		}

		$parentField = ConversionManager::getParentalField($entityTypeID, $fieldName);
		$daughterlyFields = ConversionManager::getConcernedFields(
			$parentField['ENTITY_TYPE_ID'],
			$parentField['FIELD_NAME']
		);

		if($parentField['FIELD_NAME'] !== $fieldName)
		{
			$userFieldEntity = UserFieldManager::getUserFieldEntity($parentField['ENTITY_TYPE_ID']);
			if($userFieldEntity)
			{
				$field = $userFieldEntity->GetByName($parentField['FIELD_NAME']);
				if(is_array($field))
				{
					$ID = $field['ID'];
					self::lockField($ID);
					$userFieldEntity->UpdateField($ID, $fieldData);
					self::unlockField($ID);
				}
			}
		}

		foreach($daughterlyFields as $daughterlyField)
		{
			if($daughterlyField['FIELD_NAME'] === $fieldName)
			{
				continue;
			}

			$userFieldEntity = UserFieldManager::getUserFieldEntity($daughterlyField['ENTITY_TYPE_ID']);
			if($userFieldEntity)
			{
				$field = $userFieldEntity->GetByName($daughterlyField['FIELD_NAME']);
				if(!is_array($field))
				{
					continue;
				}

				$ID = $field['ID'];
				self::lockField($ID);
				$userFieldEntity->UpdateField($ID, $fieldData);
				self::unlockField($ID);
			}
		}

		return true;
	}
	public static function onUserFieldUpdate(array $changedFields, $ID)
	{
		if($ID <= 0)
		{
			return;
		}

		//Skip locked field (for prevent loop)
		if($ID <= 0 || self::isFieldLocked($ID))
		{
			return;
		}

		if(!isset($changedFields['EDIT_FORM_LABEL'])
			&& !isset($changedFields['LIST_COLUMN_LABEL'])
			&& !isset($changedFields['LIST_FILTER_LABEL'])
		)
		{
			return;
		}

		$fields = \CUserTypeEntity::GetByID($ID);
		if(is_array($fields) && isset($fields['ENTITY_ID']) && isset($fields['FIELD_NAME']))
		{
			$fieldData = array();
			if(isset($changedFields['EDIT_FORM_LABEL']))
			{
				$fieldData['EDIT_FORM_LABEL'] = $changedFields['EDIT_FORM_LABEL'];
			}
			if(isset($changedFields['LIST_COLUMN_LABEL']))
			{
				$fieldData['LIST_COLUMN_LABEL'] = $changedFields['LIST_COLUMN_LABEL'];
			}
			if(isset($changedFields['LIST_FILTER_LABEL']))
			{
				$fieldData['LIST_FILTER_LABEL'] = $changedFields['LIST_FILTER_LABEL'];
			}

			self::synchronize($fields['ENTITY_ID'], $fields['FIELD_NAME'], array('FIELD_DATA' => $fieldData));
		}
	}
}
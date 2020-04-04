<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Main\Context;

Loc::loadMessages(__FILE__);

class FieldSynchronizer
{
	protected $isCreateMode = false;

	public function getSynchronizeFields($schemeId, $fieldNames, $invoicePayerEntityName = null)
	{
		$this->isCreateMode = false;

		$syncFieldCodes = array();
		$srcFieldMap = $this->getFieldMap($schemeId, $fieldNames);
		foreach($srcFieldMap as $entityTypeName => $entityFields)
		{
			foreach($entityFields as $fieldName => $entityField)
			{
				$oldFieldCode = $entityField['OLD_FIELD_CODE'];
				$newFieldCode = $entityField['NEW_FIELD_CODE'];

				if($oldFieldCode == $newFieldCode)
				{
					continue;
				}

				if($newFieldCode)
				{
					continue;
				}

				$syncFieldCodes[] = $oldFieldCode;
			}
		}

		return $syncFieldCodes;
	}

	public function replacePostFields($schemeId, &$fields, &$dependencies, $invoicePayerEntityName = null)
	{
		$this->isCreateMode = true;

		$srcFieldCodes = array_keys($fields);
		$srcFieldMap = $this->getFieldMap($schemeId, $srcFieldCodes);

		foreach($srcFieldMap as $entityTypeName => $entityFields)
		{
			foreach($entityFields as $fieldName => $entityField)
			{
				$oldFieldCode = $entityField['OLD_FIELD_CODE'];
				$newFieldCode = $entityField['NEW_FIELD_CODE'];

				if($oldFieldCode == $newFieldCode)
				{
					continue;
				}

				// replace field
				self::replaceField($fields, $entityField);

				// replace dependencies
				self::replaceFieldDependencies($dependencies, $entityField);

				unset($fields[$entityField['OLD_FIELD_CODE']]);
			}
		}
	}

	protected function getFieldMap($schemeId, $srcFieldCodes)
	{
		$entityTypeNames = Entity::getNames();
		$srcFieldMap = array();
		foreach($srcFieldCodes as $srcFieldCodeTmp)
		{
			foreach($entityTypeNames as $entityTypeName)
			{
				$prefix = $entityTypeName . '_';
				if(substr($srcFieldCodeTmp, 0, strlen($prefix)) != $prefix)
				{
					continue;
				}

				$srcEntity = Entity::getMap($entityTypeName);
				$srcEntityFields = EntityFieldProvider::getFieldsInternal($entityTypeName, $srcEntity);

				$fieldName = substr($srcFieldCodeTmp, strlen($prefix));
				$srcFieldMap[$entityTypeName][$fieldName] = array(
					'FIELD_NAME' => $fieldName,
					'OLD_FIELD_CODE' => $srcFieldCodeTmp,
					'OLD_FIELD' => self::findField($fieldName, $srcEntityFields),
					'NEW_FIELD_CODE' => '',
					'NEW_FIELD' => null
				);
				break;
			}
		}

		foreach($srcFieldMap as $entityTypeName => $entityFields)
		{
			$fieldNames = array();
			foreach($entityFields as $keyId => $entityField)
			{
				$fieldNames[] = $entityField['FIELD_NAME'];
			}
			if(count($fieldNames) == 0)
			{
				continue;
			}

			$synchronizedFields = $this->getReplacedSchemeFields(
				$entityTypeName,
				$schemeId,
				$fieldNames
			);

			foreach($synchronizedFields as $dstEntityTypeName => $syncFields)
			{
				$dstEntity = Entity::getMap($dstEntityTypeName);
				$dstEntityFields = EntityFieldProvider::getFieldsInternal($dstEntityTypeName, $dstEntity);

				$prefix = $dstEntityTypeName . '_';
				foreach($syncFields as $syncFieldOld => $syncFieldNew)
				{
					if(!$syncFieldNew)
					{
						continue;
					}

					$srcFieldMap[$entityTypeName][$syncFieldOld]['NEW_FIELD_CODE'] = $prefix . $syncFieldNew;
					$srcFieldMap[$entityTypeName][$syncFieldOld]['NEW_FIELD'] = self::findField($syncFieldNew, $dstEntityFields);
				}
			}

		}

		return $srcFieldMap;
	}

	protected function getFieldItemMap($oldItems, $newItems)
	{
		$itemIdMap = array();
		foreach($oldItems as $oldItem)
		{
			foreach($newItems as $newItem)
			{
				if($oldItem['VALUE'] == $newItem['VALUE'])
				{
					$itemIdMap[$oldItem['ID']] = $newItem['ID'];
				}
			}
		}

		return $itemIdMap;
	}

	protected function replaceField(&$fields, $entityField)
	{
		$oldFieldCode = $entityField['OLD_FIELD_CODE'];
		$newFieldCode = $entityField['NEW_FIELD_CODE'];
		if(!$newFieldCode || isset($fields[$newFieldCode]))
		{
			return;
		}

		// replace field codes and items
		$fields[$newFieldCode] = $fields[$oldFieldCode];
		if(!$fields[$newFieldCode]['ITEMS'])
		{
			return;
		}
		if(!$entityField['OLD_FIELD']['items'])
		{
			return;
		}
		if(!$entityField['NEW_FIELD']['items'])
		{
			return;
		}

		self::replaceFieldItems(
			$fields[$newFieldCode],
			$entityField['OLD_FIELD']['items'],
			$entityField['NEW_FIELD']['items']
		);
	}

	protected function replaceFieldItems(&$field, $oldItems, $newItems)
	{
		$itemIdMap = self::getFieldItemMap($oldItems, $newItems);
		foreach($field['ITEMS'] as $keyId => $oldItem)
		{
			foreach($itemIdMap as $oldItemId => $newItemId)
			{
				if($oldItem['ID'] != $oldItemId)
				{
					continue;
				}

				$field['ITEMS'][$keyId]['ID'] = $newItemId;
			}
		}
	}

	protected function replaceFieldDependencies(&$dependencies, $entityField)
	{
		$oldFieldCode = $entityField['OLD_FIELD_CODE'];
		$newFieldCode = $entityField['NEW_FIELD_CODE'];

		if(!$newFieldCode)
		{
			return;
		}

		foreach($dependencies as $dependencyId => $dependency)
		{
			if($dependency['IF_FIELD_CODE'] == $oldFieldCode)
			{
				$dependency['IF_FIELD_CODE'] = $newFieldCode;
				if($dependency['IF_VALUE'] && $entityField['OLD_FIELD']['items'] && $entityField['NEW_FIELD']['items'])
				{
					$itemIdMap = self::getFieldItemMap(
						$entityField['OLD_FIELD']['items'],
						$entityField['NEW_FIELD']['items']
					);
					if(isset($itemIdMap[$dependency['IF_VALUE']]))
					{
						$dependency['IF_VALUE'] = $itemIdMap[$dependency['IF_VALUE']];
					}
				}
			}

			if($dependency['DO_FIELD_CODE'] == $oldFieldCode)
			{
				$dependency['DO_FIELD_CODE'] = $newFieldCode;
			}

			$dependencies[$dependencyId] = $dependency;
		}
	}

	protected function findField($fieldName, $entityFields)
	{
		foreach($entityFields as $entityField)
		{
			if($entityField['entity_field_name'] == $fieldName)
			{
				return $entityField;
			}
		}

		return null;
	}

	protected function getXmlIdUserFieldBySystemField($entityTypeName, $fieldName)
	{
		return 'CRM_WEBFORM_' . $entityTypeName . '_' . $fieldName;
	}

	protected function getSystemFieldByUserField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName)
	{
		$srcEntityTypeId = \CCrmOwnerType::ResolveID($srcEntityTypeName);
		$entityId = \CCrmOwnerType::ResolveUserFieldEntityID($srcEntityTypeId);
		$userTypeEntity = new \CUserTypeEntity();

		$resultDb = $userTypeEntity->GetList(
			array(),
			array('ENTITY_ID' => $entityId, 'FIELD_NAME' => $srcFieldName)
		);

		if($dstField = $resultDb->Fetch())
		{
			$prefix = 'CRM_WEBFORM_' . $dstEntityTypeName . '_';

			if(substr($dstField['XML_ID'], 0, strlen($prefix)) == $prefix)
			{
				return substr($dstField['XML_ID'], strlen($prefix));
			}
		}

		return null;
	}

	protected function getUserFieldBySystemField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName)
	{
		$dstEntityTypeId = \CCrmOwnerType::ResolveID($dstEntityTypeName);
		$entityId = \CCrmOwnerType::ResolveUserFieldEntityID($dstEntityTypeId);
		$userTypeEntity = new \CUserTypeEntity();

		$xmlId = $this->getXmlIdUserFieldBySystemField($srcEntityTypeName, $srcFieldName);

		$resultDb = $userTypeEntity->GetList(
			array(),
			array('ENTITY_ID' => $entityId, 'XML_ID' => $xmlId)
		);
		if($dstField = $resultDb->Fetch())
		{
			return $dstField['FIELD_NAME'];
		}

		return null;
	}

	protected function createUserFieldBySystemField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName)
	{
		$userFieldId = null;

		$srcEntity = Entity::getMap($srcEntityTypeName);
		$srcEntityFields = EntityFieldProvider::getFieldsInternal($srcEntityTypeName, $srcEntity);
		$srcField = self::findField($srcFieldName, $srcEntityFields);

		if(!$srcField)
		{
			return null;
		}

		$dstFieldName = $this->getUserFieldBySystemField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName);
		if($dstFieldName || !$this->isCreateMode)
		{
			return $dstFieldName;
		}


		$typeId = null;
		$userFieldSettings = null;
		switch($srcField['type'])
		{
			case 'checkbox':
			case 'radio':
			case 'list':
				$typeId = 'enumeration';
				if($srcField['type'] == 'checkbox' || $srcField['type'] == 'radio')
				{
					$userFieldSettings['DISPLAY'] = 'CHECKBOX';
				}
				else
				{
					$userFieldSettings['DISPLAY'] = 'LIST';
				}
				break;

			case 'file':
			case 'string':
			case 'date':
				$typeId = $srcField['type'];
				break;

			default:
				$typeId = 'string';
		}

		$xmlId = $this->getXmlIdUserFieldBySystemField($srcEntityTypeName, $srcFieldName);

		$dstEntityTypeId = \CCrmOwnerType::ResolveID($dstEntityTypeName);
		$entityId = \CCrmOwnerType::ResolveUserFieldEntityID($dstEntityTypeId);
		$userTypeEntity = new \CUserTypeEntity();

		$resultDb = $userTypeEntity->GetList(
			array(),
			array('ENTITY_ID' => $entityId, 'XML_ID' => $xmlId)
		);
		if($dstField = $resultDb->Fetch())
		{
			return $dstField['FIELD_NAME'];
		}
		if(!$this->isCreateMode)
		{
			return null;
		}


		do
		{
			$dstFieldName = 'UF_CRM_'.strtoupper(uniqid());
			$resultDb = $userTypeEntity->GetList(
				array(),
				array('ENTITY_ID' => $entityId, 'FIELD_NAME' => $dstFieldName)
			);
		}
		while(is_array($resultDb->Fetch()));

		$dstField = array(
			'XML_ID' => $xmlId,
			'FIELD_NAME' => $dstFieldName,
			'ENTITY_ID' => $entityId,
			'USER_TYPE_ID' => $typeId,
			'SORT' =>100,
			'MULTIPLE' => $srcField['multiple'] ? 'Y' : 'N',
			'MANDATORY' => $srcField['required'] ? 'Y' : 'N',
			'SHOW_FILTER' => 'N',
			'SHOW_IN_LIST' => 'N',
			'EDIT_FORM_LABEL' => array(LANGUAGE_ID => $srcField['caption']),
			'LIST_COLUMN_LABEL' => array(LANGUAGE_ID => $srcField['caption']),
			'LIST_FILTER_LABEL' => array(LANGUAGE_ID => $srcField['caption']),
		);

		if($userFieldSettings)
		{
			$dstField['SETTINGS'] = $userFieldSettings;
		}

		$userFieldId = $userTypeEntity->Add($dstField);
		if($userFieldId && $typeId === 'enumeration' && $srcField['items'])
		{
			$enumList = array();
			$enumQty = 0;
			foreach($srcField['items'] as $item){
				$enum = array(
					'XML_ID' => $item['ID'],
					'VALUE' => $item['VALUE'],
					'SORT' => ($enumQty + 1) * 10,
				);
				$enumList["n{$enumQty}"] = $enum;
				$enumQty++;
			}

			$enumEntity = new \CUserFieldEnum();
			$enumEntity->SetEnumValues($userFieldId, $enumList);

			$GLOBALS['CACHE_MANAGER']->ClearByTag('crm_fields_list_' . $entityId);
		}

		return $dstFieldName;
	}

	protected function getSynchronizedSystemField($srcEntityTypeName, $srcFieldName, $dstEntityTypeName, $dstEntityFieldNames)
	{
		$interChangeableFields = array(
			array('NAME', 'TITLE', 'ORDER_TOPIC')
		);

		if(in_array($srcFieldName, $dstEntityFieldNames))
		{
			return $srcFieldName;
		}

		$dstFieldName = null;
		foreach($interChangeableFields as $icGroup)
		{
			// src field not in group
			if(!in_array($srcFieldName, $icGroup))
			{
				continue;
			}

			foreach($icGroup as $icFieldName)
			{
				// field does not existed in dst entity
				if(!in_array($icFieldName, $dstEntityFieldNames))
				{
					continue;
				}

				$dstFieldName = $icFieldName;
				break;
			}
		}

		// add userfield as a entity field
		if(!$dstFieldName)
		{
			$dstFieldName = $this->createUserFieldBySystemField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName);
		}

		return $dstFieldName;
	}

	protected function getSynchronizedUserFields($srcEntityTypeId, $dstEntityTypeId, $userFieldNames)
	{
		if(!$this->isCreateMode)
		{
			$synchronizedFieldNameMap = array();
			$newFields = UserFieldSynchronizer::getSynchronizationFields($srcEntityTypeId, $dstEntityTypeId, null, true);
			foreach($newFields as $newDstFieldName)
			{
				if(!in_array($newDstFieldName, $userFieldNames))
				{
					continue;
				}

				$synchronizedFieldNameMap[$newDstFieldName] = '';
			}
			foreach(UserFieldSynchronizer::$existedFieldNameMap as $existedSrcFieldName => $existedDstFieldName)
			{
				if(!in_array($existedSrcFieldName, $userFieldNames))
				{
					continue;
				}

				$synchronizedFieldNameMap[$existedSrcFieldName] = $existedDstFieldName;
			}

			return $synchronizedFieldNameMap;
		}
		else
		{
			return UserFieldSynchronizer::synchronize(
				$srcEntityTypeId,
				$dstEntityTypeId,
				Context::getCurrent()->getLanguage(),
				array('FIELD_NAME' => $userFieldNames)
			);
		}
	}

	/**
	 * Synchronize source type fields with destination type fields.
	 * Matches are searched by comparing field labels.
	 * If a source field is not found in the destination type, it will be created there.

	 * @param int $srcEntityTypeName Source Entity Type Name
	 * @param int $dstEntityTypeName Destination Entity Type Name
	 * @param array $srcFieldNames Field names for synchronizing
	 * @return array $synchronizedFieldMap
	 */
	protected function synchronizeFields($srcEntityTypeName, $dstEntityTypeName, $srcFieldNames)
	{
		$synchronizedFieldMap = array();
		$entityFieldNames = array();
		$userFieldNames = array();

		$dstEntityFieldNames = array();
		$dstEntity = Entity::getMap($dstEntityTypeName);

		//TODO: speed, refactor to fields without userfields
		$dstEntityFields = EntityFieldProvider::getFieldsInternal($dstEntityTypeName, $dstEntity);
		foreach($dstEntityFields as $dstEntityField)
		{
			$dstEntityFieldNames[] = $dstEntityField['entity_field_name'];
		}

		$srcEntityTypeId = \CCrmOwnerType::ResolveID($srcEntityTypeName);
		$dstEntityTypeId = \CCrmOwnerType::ResolveID($dstEntityTypeName);

		if(!$srcEntityTypeId)
		{
			return array();
		}

		if(!$dstEntityTypeId)
		{
			return array();
		}

		foreach($srcFieldNames as $fieldName)
		{
			if(substr($fieldName, 0, 3) == 'UF_')
			{
				$userFieldNames[] = $fieldName;
			}
			else
			{
				$dstFieldName = $this->getSynchronizedSystemField($srcEntityTypeName, $fieldName, $dstEntityTypeName, $dstEntityFieldNames);
				if(!$dstFieldName && $this->isCreateMode)
				{
					continue;
				}

				$entityFieldNames[$fieldName] = $dstFieldName;
			}
		}

		if(count($entityFieldNames) > 0)
		{
			$synchronizedFieldMap = array_merge($synchronizedFieldMap, $entityFieldNames);
		}

		if(count($userFieldNames) > 0)
		{
			$existedUserFieldToSystemFieldNameMap = array();
			foreach($userFieldNames as $srcFieldKey => $srcFieldName)
			{
				$dstFieldName = $this->getSystemFieldByUserField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName);
				if($dstFieldName)
				{
					$existedUserFieldToSystemFieldNameMap[$srcFieldName] = $dstFieldName;
					unset($userFieldNames[$srcFieldKey]);
				}
			}

			$synchronizedUserFieldNameMap = $this->getSynchronizedUserFields($srcEntityTypeId, $dstEntityTypeId, $userFieldNames);
			$synchronizedFieldMap = array_merge($synchronizedFieldMap, $existedUserFieldToSystemFieldNameMap, $synchronizedUserFieldNameMap);
		}

		return $synchronizedFieldMap;
	}

	/**
	 * Synchronize source type fields with destination type fields.
	 * Matches are searched by comparing field labels.
	 * If a source field is not found in the destination type, it will be created there.
	 *
	 * @param int $srcEntityTypeName Source Entity Type Name
	 * @param int $dstSchemeId Destination Scheme ID
	 * @param array $fieldNames Field names for synchronizing
	 * @return array $synchronizedFieldMap
	 */
	protected function getReplacedSchemeFields($srcEntityTypeName, $dstSchemeId, $fieldNames)
	{
		$synchronizedFieldMap = array();
		$scheme = Entity::getSchemes($dstSchemeId);

		if(in_array($srcEntityTypeName, $scheme['ENTITIES']))
		{
			foreach($fieldNames as $fieldName)
			{
				$synchronizedFieldMap[$srcEntityTypeName][$fieldName] = $fieldName;
			}

			return $synchronizedFieldMap;
		}

		foreach($scheme['ENTITIES'] as $dstEntityTypeName)
		{

			if($dstEntityTypeName == \CCrmOwnerType::InvoiceName)
			{
				continue;
			}

			$synchronizedFieldMap[$dstEntityTypeName] = $this->synchronizeFields(
				$srcEntityTypeName,
				$dstEntityTypeName,
				$fieldNames
			);

			//TODO: remove for creating fields in all entities
			break;
		}

		return $synchronizedFieldMap;
	}
}

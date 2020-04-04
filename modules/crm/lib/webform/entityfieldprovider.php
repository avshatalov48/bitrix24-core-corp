<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\Category\DealCategory;

Loc::loadMessages(__FILE__);

class EntityFieldProvider
{
	protected static $statusTypes = null;

	public static function getFields()
	{
		$plainFields = array();
		$fieldsByEntity = static::getFieldsTree();
		foreach($fieldsByEntity as $entityName => $entity)
		{
			foreach($entity['FIELDS'] as $field)
			{
				$field['entity_caption'] = $entity['CAPTION'];
				$field['entity_name'] = $entityName;

				$plainFields[] = $field;
			}
		}

		return $plainFields;
	}

	public static function getField($fieldCode)
	{
		$fields = static::getFields();
		foreach($fields as $field)
		{
			if($field['name'] == $fieldCode)
			{
				return $field;
			}
		}

		return null;
	}

	public static function getSectionFieldsTree()
	{

	}

	public static function getPresetFieldsTree()
	{
		$fieldsTree = self::getFieldsTree();
		$availableTypes = array(
			Internals\FieldTable::TYPE_ENUM_STRING,
			Internals\FieldTable::TYPE_ENUM_LIST,
			Internals\FieldTable::TYPE_ENUM_CHECKBOX,
			Internals\FieldTable::TYPE_ENUM_RADIO,
			Internals\FieldTable::TYPE_ENUM_TEXT,
			Internals\FieldTable::TYPE_ENUM_INT,
			Internals\FieldTable::TYPE_ENUM_FLOAT,
			Internals\FieldTable::TYPE_ENUM_DATE,
			Internals\FieldTable::TYPE_ENUM_DATETIME,
		);
		foreach($fieldsTree as $entityName => $entityFields)
		{
			foreach($entityFields['FIELDS'] as $fieldKey => $field)
			{
				if(!in_array($field['type'], $availableTypes))
				{
					unset($fieldsTree[$entityName]['FIELDS'][$fieldKey]);
					continue;
				}
			}
		}

		return $fieldsTree;
	}

	public static function getFieldsTree()
	{
		$fields = array();

		//TODO: do refactoring
		$fields['CATALOG'] = array(
			'CAPTION' => 'Other',
			'FIELDS' => array(
				array(
					'type' => 'list',
					'entity_field_name' => 'PRODUCT',
					'entity_name' => '',
					'name' => 'PRODUCT',
					'caption' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_PRODUCT'),
					'multiple' => true,
					'required' => true,
				)
			)
		);

		$fields[\CCrmOwnerType::ActivityName] = array(
			'CAPTION' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Activity),
			'FIELDS' => array(
				array(
					'type' => 'string',
					'entity_field_name' => 'SUBJECT',
					'entity_name' => \CCrmOwnerType::ActivityName,
					'name' => \CCrmOwnerType::ActivityName . '_SUBJECT',
					'caption' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_ACTIVITY_SUBJECT'),
					'multiple' => false,
					'required' => false,
				),
				array(
					'type' => 'checkbox',
					'entity_field_name' => 'COMPLETED',
					'entity_name' => \CCrmOwnerType::ActivityName,
					'name' => \CCrmOwnerType::ActivityName . '_COMPLETED',
					'caption' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_ACTIVITY_COMPLETED'),
					'multiple' => false,
					'required' => false,
					'items' => array(
						array('ID' => 'N', 'VALUE' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_NO')),
						array('ID' => 'Y', 'VALUE' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_YES'))
					)
				)
			)
		);

		$map = Entity::getMap();
		foreach($map as $entityName => $entity)
		{
			$fields[$entityName] = array(
				'CAPTION' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::ResolveID($entityName)),
				'FIELDS' => self::getFieldsInternal($entityName, $entity)
			);
		}

		return $fields;
	}

	public static function getFieldsDescription($fields)
	{
		$availableFields = EntityFieldProvider::getFields();

		$fieldCodeList = array();
		foreach($fields as $field)
		{
			$fieldCodeList[] = $field['CODE'];
		}

		$stringTypes = array_keys(Helper::getFieldStringTypes());
		foreach($availableFields as $fieldAvailable)
		{
			if(!in_array($fieldAvailable['name'], $fieldCodeList))
			{
				continue;
			}

			//array_walk($fields, $modifyFunction, $fieldAvailable);

			foreach($fields as $fieldKey => $field)
			{
				if($field['CODE'] != $fieldAvailable['name'])
				{
					continue;
				}

				$field['TYPE_ORIGINAL'] = $fieldAvailable['type'];
				$field['MULTIPLE_ORIGINAL'] = $fieldAvailable['multiple'];
				$field['VALUE_TYPE_ORIGINAL'] = $fieldAvailable['value_type'] ? $fieldAvailable['value_type'] : array();

				$isSetOriginalType = ($field['TYPE'] != 'section' && (!in_array($field['TYPE'], $stringTypes)));
				$isSetOriginalType = $isSetOriginalType && !($field['TYPE'] == 'radio' && $fieldAvailable['type'] == 'checkbox');
				if($isSetOriginalType)
				{
					$field['TYPE'] = $fieldAvailable['type'];
				}

				$field['ENTITY_NAME'] = $fieldAvailable['entity_name'];
				$field['ENTITY_FIELD_NAME'] = $fieldAvailable['entity_field_name'];
				$field['ENTITY_CAPTION'] = $fieldAvailable['entity_caption'];
				$field['ENTITY_FIELD_CAPTION'] = $fieldAvailable['caption'];
				//$field['MULTIPLE'] = $fieldAvailable['multiple'];
				if(isset($fieldAvailable['items']) && is_array($fieldAvailable['items']))
				{
					if(!isset($field['ITEMS']) || !is_array($field['ITEMS']))
					{
						$field['ITEMS'] = array();
					}

					$itemsTmp = array_values($field['ITEMS']);
					$field['ITEMS'] = array();
					foreach($fieldAvailable['items'] as $availableItem)
					{
						foreach($itemsTmp as $item)
						{
							if($item['ID'] != $availableItem['ID'])
							{
								continue;
							}

							if(isset($item['VALUE']) && strlen(trim($item['VALUE'])) > 0)
							{
								$availableItem['VALUE'] = (string) $item['VALUE'];
							}
							if(isset($item['SELECTED']))
							{
								$availableItem['SELECTED'] = (bool) $item['SELECTED'];
							}

							break;
						}

						$field['ITEMS'][] = $availableItem;
					}
				}

				$fields[$fieldKey] = $field;
			}
		}

		return $fields;
	}

	public static function getBooleanFieldItems()
	{
		return array(
			array('ID' => 'N', 'VALUE' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_NO')),
			array('ID' => 'Y', 'VALUE' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_YES')),
		);
	}

	public static function getFieldsInternal($entityName, $entity)
	{
		$className = $entity['CLASS_NAME'];
		$fieldInfoMethodName = isset($entity['GET_FIELDS_CALL']) ? $entity['GET_FIELDS_CALL'] : Entity::getDefaultFieldsInfoMethod();
		$ufEntityId = $className::$sUFEntityID;

		if(is_array($fieldInfoMethodName))
		{
			$fieldsFunction = $fieldInfoMethodName;
			$isAlreadyPreparedFields = true;
		}
		else
		{
			$fieldsFunction = array($className, $fieldInfoMethodName);
			$isAlreadyPreparedFields = false;
		}

		if(!is_callable($fieldsFunction))
		{
			throw new SystemException('Provider fields method not found in "' . $entity['CLASS_NAME'] . '".');
		}

		$fieldsInfo = call_user_func_array($fieldsFunction, array());
		$userFieldsInfo = array();
		self::prepareUserFieldsInfo($userFieldsInfo, $ufEntityId);
		if($isAlreadyPreparedFields)
		{
			$commonExcludedFields = Entity::getEntityMapCommonExcludedFields();
			foreach($fieldsInfo as $fieldId => $fieldData)
			{
				if(!in_array($fieldId, $commonExcludedFields))
				{
					continue;
				}

				unset($fieldsInfo[$fieldId]);
			}
			//self::prepareMultiFieldsInfo($userFieldsInfo);
			//$fieldsInfo = $fieldsInfo + $userFieldsInfo;
		}
		else
		{
			$fieldsInfo = $fieldsInfo + $userFieldsInfo;
			if ($entity['HAS_MULTI_FIELDS'])
			{
				self::prepareMultiFieldsInfo($fieldsInfo);
			}
			$fieldsInfo = self::prepareFields($fieldsInfo);
		}

		return self::prepareWebFormFields($entityName, $fieldsInfo);
	}

	protected static function prepareMultiFieldsInfo(&$fieldsInfo)
	{
		$typesID = array_keys(\CCrmFieldMulti::GetEntityTypeInfos());
		foreach($typesID as $typeID)
		{
			$fieldsInfo[$typeID] = array(
				'TYPE' => 'crm_multifield',
				'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Multiple)
			);
		}
	}

	protected static function prepareUserFieldsInfo(&$fieldsInfo, $entityTypeID)
	{
		$userType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entityTypeID);
		$userType->PrepareFieldsInfo($fieldsInfo);
	}

	protected static function isFieldAllowed($fieldID, $fieldInfo)
	{
		$attributes = isset($fieldInfo['ATTRIBUTES']) ? $fieldInfo['ATTRIBUTES'] : array();

		// Skip hidden fields
		if(in_array(\CCrmFieldInfoAttr::Hidden, $attributes, true))
		{
			return false;
		}

		// Skip deprecated fields
		if(in_array(\CCrmFieldInfoAttr::Deprecated, $attributes, true))
		{
			return false;
		}

		// Skip readonly fields
		if(in_array(\CCrmFieldInfoAttr::ReadOnly, $attributes, true))
		{
			return false;
		}

		// Skip excluded fields
		if(in_array($fieldID, Entity::getEntityMapCommonExcludedFields()))
		{
			return false;
		}

		// Skip wrong named fields
		if(strpos($fieldID, '.') !== false || strpos($fieldID, '[') !== false || strpos($fieldID, ']') !== false)
		{
			return false;
		}

		return true;
	}

	protected static function prepareWebFormFields($entityName, &$fieldsInfo)
	{
		$statusTypes = self::getStatusTypes();
		$multiFieldTypes = \CCrmFieldMulti::GetEntityTypes();
		$multiFieldTypeInfos = \CCrmFieldMulti::GetEntityTypeInfos();


		$commonExcludedFieldCodes = Entity::getCommonExcludedFieldCodes();
		$fieldList = array();
		foreach($fieldsInfo as $fieldId => $field)
		{
			$formFieldCaption = $field['isDynamic'] ? $field['formLabel'] : Entity::getFieldCaption($entityName, $fieldId);
			if(!$formFieldCaption && $field['type'] != 'crm_multifield')
			{
				continue;
			}

			$formField = array(
				'type' => $field['type'],
				'entity_field_name' => $fieldId,
				'entity_name' => $entityName,
				'name' => $entityName . '_' . $fieldId,
				'caption' => $formFieldCaption,
				'multiple' => (bool) $field['isMultiple'],
				'required' => (bool) $field['isRequired'],
				'hidden' => false,
			);


			if(in_array($formField['name'], $commonExcludedFieldCodes))
			{
				continue;
			}

			switch($formField['type'])
			{
				case 'crm':
				case 'iblock_element':
				case 'employee':
				case 'iblock_section':
					continue 2;
					break;

				case 'string':
					if (
						(isset($field['settings']['ROWS']) && intval($field['settings']['ROWS']) > 1)
						||
						$fieldId == 'COMMENTS'
					)
					{
						$formField['type'] = 'text';
					}
					break;

				case 'enumeration':
					if($field['settings']['DISPLAY'] == 'CHECKBOX')
					{
						$formField['type'] = $formField['multiple'] ? 'checkbox' : 'radio';
					}
					else
					{
						$formField['type'] = 'list';
					}
					$formField['items'] = $field['items']; // array(array('ID' => 1, 'VALUE' => 'Text'))
					break;

				case 'char':
				case 'boolean':
					$formField['type'] = 'checkbox';
					/*
					$formField['items'] = array(
						array('ID' => 'N', 'VALUE' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_NO')),
						array('ID' => 'Y', 'VALUE' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_YES')),
					);
					*/

					break;

				case 'double':
					$formField['type'] = Internals\FieldTable::TYPE_ENUM_FLOAT;
					break;

				case 'integer':
					$formField['type'] = Internals\FieldTable::TYPE_ENUM_INT;
					break;

				case 'crm_status':
					$formField['type'] = 'list';
					if(is_array($field['statusType']))
					{
						$field['statusType'] = $field['statusType']['ID'];
					}
					if($field['statusType'] && isset($statusTypes[$field['statusType']]))
					{
						$formField['items'] = $statusTypes[$field['statusType']];
					}
					break;

				case 'crm_multifield':
					$formField['type'] = 'typed_string';
					if(isset($multiFieldTypeInfos[$fieldId]))
					{
						$formField['caption'] = $multiFieldTypeInfos[$fieldId]['NAME'];
					}
					if(isset($multiFieldTypes[$fieldId]))
					{
						$formField['value_type'] = array();
						foreach($multiFieldTypes[$fieldId] as $multiFieldCode => $multiField)
						{
							$formField['value_type'][] = array(
								'ID' => $multiFieldCode,
								'VALUE' => $multiField['SHORT']
							);
						}
					}
					break;
			}

			if ($formField['name'] == 'DEAL_STAGE_ID')
			{
				$formField['itemsByCategory'] = array();
				$categoryList = DealCategory::getAll();
				foreach($categoryList as $category)
				{
					$dealStages = DealCategory::getStageInfos($category['ID']);
					foreach ($dealStages as $dealStageId => $dealStage)
					{
						$formField['itemsByCategory'][$category['ID']][] = array(
							'ID' => $dealStageId,
							'VALUE' => $dealStage['NAME']
						);
					}
				}
			}

			$fieldList[] = $formField;
		}

		return $fieldList;
	}

	protected static function getStatusTypes()
	{
		if(self::$statusTypes === null)
		{
			self::$statusTypes = array();
			$statusDb = StatusTable::getList(array('order' => array('ENTITY_ID', 'SORT', 'NAME')));
			while($status = $statusDb->fetch())
			{
				self::$statusTypes[$status['ENTITY_ID']][] = array(
					'ID' => $status['STATUS_ID'],
					'VALUE' => $status['NAME'],
				);
			}
		}

		return self::$statusTypes;
	}

	protected static function prepareFields(&$fieldsInfo)
	{
		$result = array();

		foreach($fieldsInfo as $fieldID => &$fieldInfo)
		{
			if(!self::isFieldAllowed($fieldID, $fieldInfo))
			{
				unset($fieldsInfo[$fieldID]);
				continue;
			}

			$attributes = isset($fieldInfo['ATTRIBUTES']) ? $fieldInfo['ATTRIBUTES'] : array();

			$fieldType = $fieldInfo['TYPE'];
			$field = array(
				'type' => $fieldType,
				'isRequired' => in_array(\CCrmFieldInfoAttr::Required, $attributes, true),
				'isImmutable' => in_array(\CCrmFieldInfoAttr::Immutable, $attributes, true),
				'isMultiple' => in_array(\CCrmFieldInfoAttr::Multiple, $attributes, true),
				'isDynamic' => in_array(\CCrmFieldInfoAttr::Dynamic, $attributes, true)
			);

			$field['settings'] = isset($fieldInfo['SETTINGS']) ? $fieldInfo['SETTINGS'] : array();
			if($fieldType === 'enumeration')
			{
				$field['items'] = isset($fieldInfo['ITEMS']) ? $fieldInfo['ITEMS'] : array();
			}
			elseif($fieldType === 'crm_status')
			{
				$field['statusType'] = isset($fieldInfo['CRM_STATUS_TYPE']) ? $fieldInfo['CRM_STATUS_TYPE'] : '';
			}
			elseif ($fieldType === 'product_property')
			{
				$field['propertyType'] = isset($fieldInfo['PROPERTY_TYPE']) ? $fieldInfo['PROPERTY_TYPE'] : '';
				$field['userType'] = isset($fieldInfo['USER_TYPE']) ? $fieldInfo['USER_TYPE'] : '';
				$field['title'] = isset($fieldInfo['NAME']) ? $fieldInfo['NAME'] : '';
				if ($field['propertyType'] === 'L')
					$field['values'] = isset($fieldInfo['VALUES']) ? $fieldInfo['VALUES'] : array();
			}

			if(isset($fieldInfo['LABELS']) && is_array($fieldInfo['LABELS']))
			{
				$labels = $fieldInfo['LABELS'];
				if(isset($labels['LIST']))
				{
					$field['listLabel'] = $labels['LIST'];
				}
				if(isset($labels['FORM']))
				{
					$field['formLabel'] = $labels['FORM'];
				}
				if(isset($labels['FILTER']))
				{
					$field['filterLabel'] = $labels['FILTER'];
				}
			}

			$result[$fieldID] = &$field;
			unset($field);
		}
		unset($fieldInfo);

		return $result;
	}
}

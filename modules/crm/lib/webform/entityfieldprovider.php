<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

class EntityFieldProvider
{
	const TYPE_VIRTUAL = 'VIRTUAL';
	protected static $statusTypes = null;

	public static function getFields(array $hiddenTypes = [], ?int $presetId = null)
	{
		$plainFields = array();
		$fieldsByEntity = static::getFieldsTree($hiddenTypes, $presetId);
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

	public static function getField($fieldCode, $hiddenTypes = [], ?int $presetId = null)
	{
		$fields = static::getFields($hiddenTypes, $presetId);
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
				if (
					mb_strpos($entityName, 'DYNAMIC_') === 0
					&&
					in_array($field['entity_field_name'], ['CATEGORY_ID', 'STAGE_ID'])
				)
				{
					unset($fieldsTree[$entityName]['FIELDS'][$fieldKey]);
					continue;
				}

				if(!in_array($field['type'], $availableTypes))
				{
					unset($fieldsTree[$entityName]['FIELDS'][$fieldKey]);
					continue;
				}
			}
		}

		return $fieldsTree;
	}

	public static function getFieldsTree(array $hiddenTypes = [], ?int $presetId = null)
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

		$hideVirtual = in_array(self::TYPE_VIRTUAL, $hiddenTypes);
		$hideRequisites = in_array(\CCrmOwnerType::Requisite, $hiddenTypes);
		$map = Entity::getMap();
		foreach($map as $entityName => $entity)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entityName);
			if (!empty($entity['HIDDEN']) && !in_array($entityTypeId, $hiddenTypes))
			{
				continue;
			}

			$fields[$entityName] = array(
				'CAPTION' => \CCrmOwnerType::GetDescription($entityTypeId),
				'FIELDS' => self::getFieldsInternal(
					$entityName,
					$entity,
					[
						'hideVirtual' => $hideVirtual,
						'hideRequisites' => $hideRequisites,
						'presetId' => $presetId,
					]
				)
			);
		}

		return $fields;
	}

	public static function getAllFieldsDescription(?int $requisitePresetId = null)
	{
		$result = [];

		$hiddenTypes = [
			\CCrmOwnerType::SmartDocument,
			\CCrmOwnerType::SmartB2eDocument,
		];

		$availableFields = EntityFieldProvider::getFields($hiddenTypes, $requisitePresetId);
		foreach($availableFields as $fieldAvailable)
		{
			$result[] = self::getFieldDescription($fieldAvailable);
		}

		return $result;
	}

	public static function getFieldsDescription(array $fields, ?int $presetId = null)
	{
		$fieldCodeList = array();
		foreach($fields as $field)
		{
			$fieldCodeList[] = $field['CODE'];
		}

		$hiddenTypes = [
			\CCrmOwnerType::SmartDocument,
		];

		$availableFields = EntityFieldProvider::getFields($hiddenTypes, $presetId);
		foreach($availableFields as $fieldAvailable)
		{
			if(!in_array($fieldAvailable['name'], $fieldCodeList))
			{
				continue;
			}

			foreach($fields as $fieldKey => $field)
			{
				$field = self::getFieldDescription($fieldAvailable, $field);
				if(!$field)
				{
					continue;
				}

				$fields[$fieldKey] = $field;
			}
		}

		return $fields;
	}

	private static function getFieldDescription(array $fieldAvailable, array $field = [])
	{
		static $stringTypes = null;
		if ($stringTypes === null)
		{
			$stringTypes = array_keys(Helper::getFieldStringTypes());
		}


		if (!empty($field['CODE']) && $field['CODE'] != $fieldAvailable['name'])
		{
			return null;
		}

		$field['CODE'] = $fieldAvailable['name'];
		$field['TYPE_ORIGINAL'] = $fieldAvailable['type'];
		$field['MULTIPLE_ORIGINAL'] = $fieldAvailable['multiple'];
		$field['VALUE_TYPE_ORIGINAL'] = empty($fieldAvailable['value_type']) ? [] : $fieldAvailable['value_type'];

		$fieldType = $field['TYPE'] ?? null;
		$isSetOriginalType = ($fieldType != 'section' && (!in_array($fieldType, $stringTypes)));
		$isSetOriginalType = $isSetOriginalType && !($fieldType == 'radio' && $fieldAvailable['type'] === 'checkbox');
		if($isSetOriginalType)
		{
			$field['TYPE'] = $fieldAvailable['type'];
		}

		$field['ENTITY_NAME'] = $fieldAvailable['entity_name'];
		$field['ENTITY_FIELD_NAME'] = $fieldAvailable['entity_field_name'];
		$field['ENTITY_CAPTION'] = $fieldAvailable['entity_caption'];
		$field['ENTITY_FIELD_CAPTION'] = $fieldAvailable['caption'];
		$field['SIZE'] = $fieldAvailable['size'] ?? null;



		if(!isset($fieldAvailable['items']) || !is_array($fieldAvailable['items']))
		{
			return $field;
		}


		if(!isset($field['ITEMS']) || !is_array($field['ITEMS']))
		{
			$field['ITEMS'] = [];
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

				if(isset($item['VALUE']) && trim($item['VALUE']) <> '')
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

		return $field;
	}

	public static function getBooleanFieldItems()
	{
		return array(
			array('ID' => 'N', 'VALUE' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_NO')),
			array('ID' => 'Y', 'VALUE' => Loc::getMessage('CRM_WEBFORM_FIELD_PROVIDER_YES')),
		);
	}

	public static function getFieldsInternal($entityName, $entity, array $options = []): array
	{
		$fieldInfoMethodName = $entity['GET_FIELDS_CALL'] ?? Entity::getDefaultFieldsInfoMethod();
		if (is_array($fieldInfoMethodName) || is_callable($fieldInfoMethodName))
		{
			$fieldsFunction = $fieldInfoMethodName;
			$isAlreadyPreparedFields = true;
			$ufEntityId = null;
		}
		else
		{
			$className = $entity['CLASS_NAME'];
			$ufEntityId = $className::$sUFEntityID;
			$fieldsFunction = [$className, $fieldInfoMethodName];
			$isAlreadyPreparedFields = false;
		}

		if (!is_callable($fieldsFunction))
		{
			throw new SystemException('Provider fields method not found in "' . $entity['CLASS_NAME'] . '".');
		}

		$fieldsInfo = call_user_func_array($fieldsFunction, []);
		if ($isAlreadyPreparedFields)
		{
			$commonExcludedFields = Entity::getEntityMapCommonExcludedFields();
			foreach ($fieldsInfo as $fieldId => $fieldData)
			{
				if (!in_array($fieldId, $commonExcludedFields))
				{
					continue;
				}

				unset($fieldsInfo[$fieldId]);
			}
		}
		else
		{
			$userFieldsInfo = [];
			self::prepareUserFieldsInfo($userFieldsInfo, $ufEntityId);
			$fieldsInfo = $fieldsInfo + $userFieldsInfo;
		}

		if (!empty($entity['HAS_MULTI_FIELDS']))
		{
			self::prepareMultiFieldsInfo($fieldsInfo);
		}

		$fieldsInfo = self::prepareFields($fieldsInfo);
		$fieldsMap = self::prepareWebFormFields($entityName, $fieldsInfo);
		//Add delivery address to company/contact/lead fields
		if (in_array($entityName, [\CCrmOwnerType::CompanyName, \CCrmOwnerType::ContactName], true))
		{
			if (empty($options['hideVirtual']))
			{
				$fieldsMap[] = [
					'type' => 'string',
					'entity_field_name' => "DELIVERY_ADDRESS",
					'entity_name' => $entityName,
					'name' => "{$entityName}_DELIVERY_ADDRESS",
					'caption' => Loc::getMessage("CRM_WEBFORM_FIELD_PROVIDER_DELIVERY_ADDRESS_CAPTION"),
					'multiple' => false,
					'required' => false,
				];
			}

			/*
			$fieldsMap[] = [
				'type' => 'rq',
				'entity_field_name' => "RQ",
				'entity_name' => $entityName,
				'name' => "{$entityName}_RQ",
				'caption' => Loc::getMessage("CRM_WEBFORM_FIELD_PROVIDER_RQ_CAPTION"),
				'multiple' => false,
				'required' => false,
				'ui' => [
					'setup' => 'crm.field.requisite.setup',
				],
			];
			*/

			if (empty($options['hideRequisites']))
			{
				$entityTypeId = \CCrmOwnerType::Company; //\CCrmOwnerType::resolveID($entityName);

				$preset = !is_null($options['presetId'])
					? Requisite::instance()->getPreset($options['presetId'])
					: Requisite::instance()->getDefaultPreset($entityTypeId);

				foreach (($preset['fields'] ?? []) as $field)
				{
					if (mb_strpos($field['name'], 'RQ_') !== 0)
					{
						$field['name'] = 'RQ_' . $field['name'];
					}

					$fieldName = $field['name'] ?? '';
					$fieldsMap[] = [
						'type' => $field['type'],
						'entity_field_name' => $fieldName,
						'entity_name' => $entityName,
						'name' => "{$entityName}_$fieldName",
						'caption' => $field['label'] ?? '',
						'multiple' => $field['multiple'] ?? false,
						'required' => $field['required'] ?? false,
						'size' => $field['size'] ?? null,
					];
				}
			}
		}

		return $fieldsMap;
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
		// get user fields to default category only [ID: 0]
		$userType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entityTypeID, ['categoryId' => 0]);
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
		if(mb_strpos($fieldID, '.') !== false || mb_strpos($fieldID, '[') !== false || mb_strpos($fieldID, ']') !== false)
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

	/**
	 * Handler of event `main/onAfterSetEnumValues`.
	 *
	 * @param array $userField User field.
	 * @param array $items Items.
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	public static function onUpdateUserFieldItems(array $userField, array $items)
	{
		$actualItems = [];
		foreach ($items as $itemId => $item)
		{
			if (!is_numeric($itemId))
			{
				continue;
			}

			$actualItems[$itemId] = $item;
		}

		$fieldName = substr($userField['ENTITY_ID'], 4) . '_' . $userField['FIELD_NAME'];
		$rows = Internals\FieldTable::getList([
			'select' => ['FORM_ID'],
			'filter' => [
				'=CODE' => $fieldName
			],
		]);
		foreach ($rows as $row)
		{
			$isChanged = false;
			$form = new Form($row['FORM_ID']);
			$formFields = $form->getFields();
			foreach ($formFields as $index => $formField)
			{
				if ($formField['CODE'] !== $fieldName)
				{
					continue;
				}

				$fieldItems = is_array($formField['ITEMS']) ? $formField['ITEMS'] : [];
				$fieldItems = array_combine(
					array_column($fieldItems, 'ID'),
					$fieldItems
				);

				$newItems = [];
				foreach ($actualItems as $itemId => $item)
				{
					$newItem = [
						'ID' => (string)$itemId,
						'VALUE' => $item['VALUE'],
					];

					if (!empty($fieldItems[$itemId]['DISABLED']))
					{
						$newItem['DISABLED'] = $fieldItems[$itemId]['DISABLED'] === 'Y' ? 'Y' : 'N';
					}
					if (!empty($fieldItems[$itemId]['SELECTED']))
					{
						$newItem['SELECTED'] = $fieldItems[$itemId]['SELECTED'] === 'Y' ? 'Y' : 'N';
					}

					$newItems[] = $newItem;
				}

				$formField['ITEMS'] = $newItems;
				$formFields[$index] = $formField;
				$isChanged = true;
			}

			if ($isChanged)
			{
				$form->merge(['FIELDS' => $formFields]);
				$form->save();
			}
		}
	}
}

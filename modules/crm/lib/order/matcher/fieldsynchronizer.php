<?php
namespace Bitrix\Crm\Order\Matcher;

use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Order\Matcher\Internals\FormTable;
use Bitrix\Crm\Order\Matcher\Internals\OrderPropsMatchTable;
use Bitrix\Crm\Order\Property;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Entity;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Internals\Input\File;
use Bitrix\Sale\Internals\OrderPropsGroupTable;
use Bitrix\Sale\Internals\OrderPropsRelationTable;
use Bitrix\Sale\PaySystem\Manager as PaySystemManager;
use Bitrix\Sale\Internals\OrderPropsTable;

class FieldSynchronizer
{
	const FIELD_TYPE_EMAIL = 'email';
	const FIELD_TYPE_PHONE = 'phone';
	const FIELD_TYPE_INT = 'integer';
	const FIELD_TYPE_FLOAT = 'double';
	const FIELD_TYPE_STRING = 'string';
	const FIELD_TYPE_TYPED_STRING = 'typed_string';
	const FIELD_TYPE_LIST = 'list';
	const FIELD_TYPE_LIST_CHECKBOX = 'listCheckbox';
	const FIELD_TYPE_CHECKBOX = 'checkbox';
	const FIELD_TYPE_RADIO = 'radio';
	const FIELD_TYPE_TEXT = 'text';
	const FIELD_TYPE_FILE = 'file';
	const FIELD_TYPE_DATE = 'date';
	const FIELD_TYPE_DATETIME = 'datetime';
	const FIELD_TYPE_LOCATION = 'location';
	const FIELD_TYPE_ADDRESS = 'address';

	protected static $statusTypes = null;
	protected static $countryId = null;
	protected static $requisitePresetsInfo = null;
	protected static $relations = null;

	protected static $fieldsTree = [];

	protected static function clearFieldsCache()
	{
		static::$fieldsTree = [];
	}

	public static function getTypeList()
	{
		// todo LANG
		return array(
			self::FIELD_TYPE_EMAIL => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_EMAIL'),
			self::FIELD_TYPE_INT => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_INT1'),
			self::FIELD_TYPE_FLOAT => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_FLOAT'),
			self::FIELD_TYPE_PHONE => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_PHONE'),
			self::FIELD_TYPE_LIST => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_LIST'),
			self::FIELD_TYPE_LIST_CHECKBOX => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_LIST'),

			self::FIELD_TYPE_DATE => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_DATE'),
			self::FIELD_TYPE_DATETIME => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_DATETIME'),
			self::FIELD_TYPE_CHECKBOX => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_CHECKBOX'),
			self::FIELD_TYPE_RADIO => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_RADIO'),
			self::FIELD_TYPE_TEXT => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_TEXT'),
			self::FIELD_TYPE_FILE => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_FILE'),
			self::FIELD_TYPE_STRING => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_STRING'),
			self::FIELD_TYPE_TYPED_STRING => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_TYPED_STRING'),
			self::FIELD_TYPE_LOCATION => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_LOCATION'),
			self::FIELD_TYPE_ADDRESS => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_ADDRESS'),
		);
	}

	public static function getFieldStringTypes()
	{
		$types = array(
			self::FIELD_TYPE_PHONE,
			self::FIELD_TYPE_EMAIL,
			self::FIELD_TYPE_INT,
			self::FIELD_TYPE_FLOAT,
		);

		$names = static::getTypeList();

		$result = array();

		foreach($types as $type)
		{
			$result[$type] = $names[$type];
		}

		return $result;
	}

	public static function getDefaultCountryId()
	{
		if (static::$countryId === null)
		{
			static::$countryId = EntityPreset::getCurrentCountryId();
		}

		return static::$countryId;
	}

	public static function getRequisitePresetsInfo()
	{
		if (static::$requisitePresetsInfo === null)
		{
			static::$requisitePresetsInfo = EntityPreset::getActiveItemList();
		}

		return static::$requisitePresetsInfo;
	}

	public static function getFieldFullCode($field)
	{
		$code = '';

		if ((int)$field['CRM_ENTITY_TYPE'] === \CCrmOwnerType::Order)
		{
			return FieldSynchronizer::getOrderPropertyName($field['CODE'], $field['ID']);
		}

		if ((int)$field['CRM_ENTITY_TYPE'] === \CCrmOwnerType::Contact)
		{
			$code = \CCrmOwnerType::ContactName;
		}
		elseif ((int)$field['CRM_ENTITY_TYPE'] === \CCrmOwnerType::Company)
		{
			$code = \CCrmOwnerType::CompanyName;
		}

		switch ($field['CRM_FIELD_TYPE'])
		{
			case BaseEntityMatcher::GENERAL_FIELD_TYPE:
				$code .= '_'.$field['CRM_FIELD_CODE'];
				break;

			case BaseEntityMatcher::MULTI_FIELD_TYPE:
				$parsedName = \CCrmFieldMulti::ParseComplexName($field['CRM_FIELD_CODE'], true);

				if (!empty($parsedName))
				{
					$code .= '_'.$parsedName['TYPE'];
				}
				break;

			case BaseEntityMatcher::REQUISITE_FIELD_TYPE:
				if ($field['CRM_FIELD_CODE'] === 'RQ_ADDR')
				{
					$code .= '_'.$field['SETTINGS']['RQ_ADDR_CODE']
						.'_'.$field['SETTINGS']['RQ_PRESET_ID']
						.'_'.$field['SETTINGS']['RQ_ADDR_TYPE'];
				}
				else
				{
					$code .= '_'.$field['CRM_FIELD_CODE'].'_'.$field['SETTINGS']['RQ_PRESET_ID'];
				}

				break;

			case BaseEntityMatcher::BANK_DETAIL_FIELD_TYPE:
				$code .= '_'.$field['CRM_FIELD_CODE'].'_'.$field['SETTINGS']['RQ_PRESET_ID'];

				break;
		}

		return $code;
	}

	public static function getOrderPropertyName($code, $id = null)
	{
		$name = \CCrmOwnerType::OrderName.'_';

		if (!empty($code))
		{
			if (mb_substr($code, 0, mb_strlen($name)) === $name)
			{
				$name = $code;
			}
			else
			{
				$name .= $code;
			}
		}
		elseif (!empty($id))
		{
			$name .= $id;
		}
		else
		{
			$name .= uniqid();
		}

		return $name;
	}

	public static function getOrderFieldsDescription($personTypeId)
	{
		$fields = [];

		$filter = [
			'=PERSON_TYPE_ID' => $personTypeId,
			[
				'LOGIC' => 'OR',
				'=PROP_RELATION.ENTITY_ID' => null,
				'!PROP_RELATION.ENTITY_TYPE' => 'L'
			]
		];

		$personTypePropertiesIterator = Property::getList([
			'filter' => $filter,
			'order' => ['SORT' => 'ASC'],
			'runtime' => [
				new Entity\ReferenceField(
					'PROP_RELATION',
					'\Bitrix\Sale\Internals\OrderPropsRelationTable',
					[
						'=this.ID' => 'ref.PROPERTY_ID',
					]
				)
			],
			'group' => ['CODE']
		]);

		foreach ($personTypePropertiesIterator as $property)
		{
			$propertyName = static::getOrderPropertyName($property['CODE'], $property['ID']);

			$field = [
				'id' => $property['ID'],
				'type' => static::getCrmFieldType($property),
				'entity_field_name' => $property['CODE'],
				'entity_name' => \CCrmOwnerType::OrderName,
				'name' => $propertyName,
				'caption' => $property['NAME'],
				'original_caption' => $property['NAME'],
				'multiple' => $property['MULTIPLE'] === 'Y',
				'required' => $property['REQUIRED'] === 'Y',
				'hidden' => false,
				'active' => $property['ACTIVE'] === 'Y',
				'util' => $property['UTIL'] === 'Y',
				'user_props' => $property['USER_PROPS'] === 'Y',
				'is_location' => $property['IS_LOCATION'] === 'Y',
				'is_location4tax' => $property['IS_LOCATION4TAX'] === 'Y',
				'is_profile_name' => $property['IS_PROFILE_NAME'] === 'Y',
				'is_payer' => $property['IS_PAYER'] === 'Y',
				'is_email' => $property['IS_EMAIL'] === 'Y',
				'is_phone' => $property['IS_PHONE'] === 'Y',
				'is_zip' => $property['IS_ZIP'] === 'Y',
				'is_address' => $property['IS_ADDRESS'] === 'Y',
				'value' => $property['DEFAULT_VALUE'],
			];

			if ($property['TYPE'] === 'FILE')
			{
				$items = $property['DEFAULT_VALUE'];

				if (!is_array($items) || array_key_exists('ID', $items))
				{
					$items = [$items];
				}

				$cnt = 0;

				foreach ($items as $item)
				{
					$fileId = !empty($item['ID']) ? $item['ID'] : $item;

					if (!empty($fileId))
					{
						$file = \CFile::GetFileArray($fileId);

						if (!empty($file))
						{
							$field['items'][] = $file + [
									'LIST_ORDER' => $property['MULTIPLE'] === 'Y' ? "[$cnt]" : '',
								];
						}

						$cnt++;
					}
				}

				if (empty($field['items']))
				{
					$field['items'][] = [
						'LIST_ORDER' => $property['MULTIPLE'] === 'Y' ? "[0]" : '',
					];
				}
			}

			static::initFieldsByType($field, $property);

			$fields[] = $field;
		}

		return $fields;
	}

	protected static function initFieldsByType(&$field, $property)
	{
		switch ($property['TYPE'])
		{
			case 'Y/N':
				if ($property['MULTIPLE'] === 'Y')
				{
					$field['items']= [];

					if (!empty($property['DEFAULT_VALUE']))
					{
						if (!is_array($property['DEFAULT_VALUE']))
						{
							$property['DEFAULT_VALUE'] = [$property['DEFAULT_VALUE']];
						}

						foreach ($property['DEFAULT_VALUE'] as $key => $value)
						{
							$field['items'][] = [
								'ID' => $key,
								'VALUE' => '',
								'NAME' => '',
								'CHECKED' => $value === 'Y' ? 'checked' : ''
							];
						}
					}
				}
				else
				{
					$field['checked'] = $property['DEFAULT_VALUE'] === 'Y' ? 'checked' : '';
				}

				break;
			case 'ENUM':
				$field['items']= [];

				$result = \CSaleOrderPropsVariant::GetList(($b='SORT'), ($o='ASC'), array('ORDER_PROPS_ID' => $property['ID']));
				while ($row = $result->Fetch())
				{
					$selected = is_array($property['DEFAULT_VALUE'])
						&& (in_array($row['VALUE'], $property['DEFAULT_VALUE']))
						|| $row['VALUE'] === $property['DEFAULT_VALUE'];

					$field['items'][] = [
						'ID' => $row['ID'],
						'VALUE' => $row['VALUE'],
						'NAME' => $row['NAME'],
						'DESCRIPTION' => $row['DESCRIPTION'],
						'SELECTED' => $selected ? 'selected' : '',
						'CHECKED' => $selected ? 'checked' : ''
					];
				}

				break;
			case 'DATE':
				$field['time'] = isset($property['SETTINGS']['TIME']) && $property['SETTINGS']['TIME'] === 'Y';
				$field['show_time'] = $field['time'] ? 'true' : '';
				break;
		}
	}

	public static function getEntityMap($entityTypeName = null)
	{
		$entityTypeMap =  [
			\CCrmOwnerType::ContactName => [
				'CLASS_NAME' => 'CCrmContact',
				'HAS_USER_FIELDS' => true,
				'HAS_MULTI_FIELDS' => true,
				'HAS_REQUISITES' => true,
			],
			\CCrmOwnerType::CompanyName => [
				'CLASS_NAME' => 'CCrmCompany',
				'HAS_USER_FIELDS' => true,
				'HAS_MULTI_FIELDS' => true,
				'HAS_REQUISITES' => true,
			]
		];

		if (!empty($entityTypeName))
		{
			return isset($entityTypeMap[$entityTypeName]) ? $entityTypeMap[$entityTypeName] : null;
		}
		else
		{
			return $entityTypeMap;
		}
	}

	public static function getFieldsTree($personTypeId, $entityName = '')
	{
		if (!isset(static::$fieldsTree[$personTypeId]))
		{
			$fields = [
				\CCrmOwnerType::OrderName => [
					'CAPTION' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Order),
					'FIELDS' => static::getOrderFieldsDescription($personTypeId)
				]
			];

			foreach (static::getEntityMap() as $name => $entity)
			{
				$fields[$name] = [
					'CAPTION' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::ResolveID($name)),
					'FIELDS' => static::getFieldsInternal($name, $entity)
				];
			}

			static::$fieldsTree[$personTypeId] = $fields;
		}

		if (!empty($entityName))
		{
			return isset(static::$fieldsTree[$personTypeId][$entityName]) ? static::$fieldsTree[$personTypeId][$entityName] : null;
		}
		else
		{
			return static::$fieldsTree[$personTypeId];
		}
	}

	protected static function getFieldsInternal($entityName, $entity)
	{
		$className = $entity['CLASS_NAME'];
		$fieldsFunction = [$className, 'GetFieldsInfo'];

		if (!is_callable($fieldsFunction))
		{
			throw new SystemException('Provider fields method not found in "'.$className.'".');
		}

		$fieldsInfo = call_user_func_array($fieldsFunction, []);

		if ($entity['HAS_USER_FIELDS'])
		{
			$userFieldsInfo = [];
			static::prepareUserFieldsInfo($userFieldsInfo, $className::$sUFEntityID);
			$fieldsInfo += $userFieldsInfo;
		}

		if ($entity['HAS_MULTI_FIELDS'])
		{
			self::prepareMultiFieldsInfo($fieldsInfo);
		}

		if ($entity['HAS_REQUISITES'])
		{
			self::prepareRequisitesInfo($entityName, $fieldsInfo);
		}

		$fieldsInfo = self::prepareFields($fieldsInfo);

		return self::prepareOrderFormFields($entityName, $fieldsInfo);
	}

	public static function getFieldCaption($entityName, $fieldId)
	{
		$caption = '';

		if ($entityName === \CCrmOwnerType::RequisiteName || $entityName === 'BANK_DETAIL')
		{
			if ($entityName === \CCrmOwnerType::RequisiteName)
			{
				$entity = EntityRequisite::getSingleInstance();
				$captionInfo = $entity->getRqFieldTitleMap();
			}
			else
			{
				$entity = EntityBankDetail::getSingleInstance();
				$captionInfo = $entity->getRqFieldTitleMap();
			}

			$countryId = static::getDefaultCountryId();

			if (isset($captionInfo[$fieldId][$countryId]))
			{
				$caption = $captionInfo[$fieldId][$countryId];
			}
		}
		elseif ($entityName === 'ADDRESS')
		{
			$captionInfo = RequisiteAddress::getLabels();

			if (isset($captionInfo[$fieldId]))
			{
				$caption = $captionInfo[$fieldId];
			}
		}
		else
		{
			$entity = static::getEntityMap($entityName);
			$fieldInfoMethodName = isset($entity['GET_FIELDS_CALL']) ? $entity['GET_FIELDS_CALL'] : 'GetFieldCaption';

			if (is_callable([$entity['CLASS_NAME'], $fieldInfoMethodName]))
			{
				$caption = $entity['CLASS_NAME']::$fieldInfoMethodName($fieldId);
			}
		}

		return $caption;
	}

	protected static function getStatusTypes()
	{
		if (self::$statusTypes === null)
		{
			self::$statusTypes = [];

			$statusDb = StatusTable::getList(['order' => ['ENTITY_ID', 'SORT', 'NAME']]);
			while ($status = $statusDb->fetch())
			{
				self::$statusTypes[$status['ENTITY_ID']][] = [
					'ID' => $status['STATUS_ID'],
					'VALUE' => $status['NAME']
				];
			}
		}

		return self::$statusTypes;
	}

	protected static function prepareOrderFormFields($entityName, &$fieldsInfo)
	{
		$statusTypes = self::getStatusTypes();
		$multiFieldTypes = \CCrmFieldMulti::GetEntityTypes();
		$multiFieldTypeInfo = \CCrmFieldMulti::GetEntityTypeInfos();

		$fieldList = [];

		foreach ($fieldsInfo as $fieldId => $field)
		{
			$formFieldCaption = $field['isDynamic'] ? $field['formLabel'] : self::getFieldCaption($entityName, $fieldId);

			if (empty($formFieldCaption) && $field['type'] !== 'crm_multifield' && $field['type'] !== 'tree')
			{
				continue;
			}

			$formField = [
				'type' => $field['type'],
				'entity_field_name' => $fieldId,
				'entity_name' => $entityName,
				'name' => $entityName.'_'.$fieldId,
				'caption' => $formFieldCaption,
				'original_caption' => $formFieldCaption,
				'multiple' => (bool)$field['isMultiple'],
				'required' => (bool)$field['isRequired'],
				'hidden' => false,
			];

			switch ($formField['type'])
			{
				case 'crm':
				case 'iblock_element':
				case 'iblock_section':
				case 'employee':
				case 'address':
				case 'resourcebooking':
					continue 2;
					break;

				case 'tree':
					$formField['tree'] = $field['tree'];
					break;

				case 'string':
					$formField['multiple'] = false;

					if (
						(isset($field['settings']['ROWS']) && (int)$field['settings']['ROWS'] > 1)
						|| $fieldId == 'COMMENTS'
					)
					{
						$formField['type'] = 'text';
					}

					break;

				case 'enumeration':
					if ($field['settings']['DISPLAY'] === 'CHECKBOX')
					{
						$formField['type'] = $formField['multiple'] ? 'checkbox' : 'radio';
					}
					else
					{
						$formField['type'] = 'list';
					}

					$formField['items'] = $field['items']; // [['ID' => 1, 'VALUE' => 'Text']]
					break;

				case 'char':
				case 'boolean':
					$formField['type'] = 'checkbox';
					$formField['multiple'] = false;
					break;

				case 'crm_status':
					$formField['type'] = 'list';

					if (is_array($field['statusType']))
					{
						if (isset($field['statusType']['ID']))
						{
							$field['statusType'] = $field['statusType']['ID'];
						}
						else
						{
							$countryId = static::getDefaultCountryId();
							$countryCode = EntityPreset::getCountryCodeById($countryId);
							$statusTypeId = "{$fieldId}_{$countryCode}";
							if (in_array($statusTypeId, $field['statusType'], true))
							{
								$field['statusType'] = $statusTypeId;
							}
						}
					}

					if ($field['statusType'] && isset($statusTypes[$field['statusType']]))
					{
						$formField['items'] = $statusTypes[$field['statusType']];
					}

					break;

				case 'crm_multifield':
					$formField['type'] = 'typed_string';
					$formField['multiple'] = false;

					if (isset($multiFieldTypeInfo[$fieldId]))
					{
						$formField['caption'] = $multiFieldTypeInfo[$fieldId]['NAME'];
						$formField['original_caption'] = $multiFieldTypeInfo[$fieldId]['NAME'];
					}

					if (isset($multiFieldTypes[$fieldId]))
					{
						$formField['value_type'] = [];

						foreach ($multiFieldTypes[$fieldId] as $multiFieldCode => $multiField)
						{
							$formField['value_type'][] = [
								'ID' => $multiFieldCode,
								'VALUE' => $multiField['SHORT']
							];
						}
					}

					break;
			}

			$fieldList[] = $formField;
		}

		if ($entityName === \CCrmOwnerType::ContactName)
		{
			array_unshift($fieldList, [
				'type' => 'string',
				'entity_field_name' => 'FULL_NAME',
				'name' => 'CONTACT_FULL_NAME',
				'entity_name' => 'CONTACT',
				'caption' => Loc::getMessage('CRM_ORDER_MATCHER_FULL_NAME'),
				'original_caption' => Loc::getMessage('CRM_ORDER_MATCHER_FULL_NAME'),
				'multiple' => false,
				'required' => false,
				'hidden' => false,
			]);
		}

		return $fieldList;
	}

	protected static function isFieldAllowed($fieldId, $fieldInfo)
	{
		$attributes = isset($fieldInfo['ATTRIBUTES']) ? $fieldInfo['ATTRIBUTES'] : [];

		// skip hidden fields
		if (in_array(\CCrmFieldInfoAttr::Hidden, $attributes, true))
		{
			return false;
		}

		// skip deprecated fields
		if (in_array(\CCrmFieldInfoAttr::Deprecated, $attributes, true))
		{
			return false;
		}

		// skip readonly fields
		if (in_array(\CCrmFieldInfoAttr::ReadOnly, $attributes, true))
		{
			return false;
		}

		// skip excluded fields
		if (in_array($fieldId, static::getEntityMapCommonExcludedFields()))
		{
			return false;
		}

		// skip wrong named fields
		if (mb_strpos($fieldId, '.') !== false || mb_strpos($fieldId, '[') !== false || mb_strpos($fieldId, ']') !== false)
		{
			return false;
		}

		return true;
	}

	public static function getEntityMapCommonExcludedFields()
	{
		$fieldCodes = [
			'ASSIGNED_BY_ID',

			//'PROBABILITY', 'DATE_PAY_BEFORE',
			'REVENUE',
			'OPPORTUNITY',

			//LEAD
			'CURRENCY_ID', 'OPENED', 'COMPANY_ID', 'CONTACT_ID',
			'CLOSED', 'EXPORT', 'BANKING_DETAILS', 'DEAL_ID', 'PERSON_TYPE_ID',

			//INVOICE
			'ACCOUNT_NUMBER', 'CURRENCY', 'DATE_BILL', 'DATE_MARKED',
			'PAY_SYSTEM_ID', 'RESPONSIBLE_ID', 'UF_DEAL_ID', 'UF_CONTACT_ID', 'PR_LOCATION',

			'ADDRESS_LEGAL',

			//'ADDRESS', 'REG_ADDRESS',
			'ADDRESS_2', 'ADDRESS_CITY', 'ADDRESS_POSTAL_CODE', 'ADDRESS_REGION',
			'ADDRESS_PROVINCE', 'ADDRESS_COUNTRY', 'ADDRESS_COUNTRY_CODE',
			'REG_ADDRESS_2', 'REG_ADDRESS_CITY', 'REG_ADDRESS_POSTAL_CODE', 'REG_ADDRESS_REGION',
			'REG_ADDRESS_PROVINCE', 'REG_ADDRESS_COUNTRY', 'REG_ADDRESS_COUNTRY_CODE',
		];

		$fieldCodes = array_merge($fieldCodes, \Bitrix\Crm\UtmTable::getCodeList());

		return $fieldCodes;
	}

	protected static function prepareFields(&$fieldsInfo)
	{
		$result = [];

		foreach ($fieldsInfo as $fieldId => $fieldInfo)
		{
			if (!empty($fieldInfo['tree']))
			{
				$result[$fieldId] = [
					'type' => $fieldInfo['type'],
					'tree' => $fieldInfo['tree']
				];
				continue;
			}

			if (!self::isFieldAllowed($fieldId, $fieldInfo))
			{
				unset($fieldsInfo[$fieldId]);
				continue;
			}

			$attributes = isset($fieldInfo['ATTRIBUTES']) ? $fieldInfo['ATTRIBUTES'] : [];

			$fieldType = $fieldInfo['TYPE'];
			$field = [
				'type' => $fieldType,
				'isRequired' => in_array(\CCrmFieldInfoAttr::Required, $attributes, true),
				'isImmutable' => in_array(\CCrmFieldInfoAttr::Immutable, $attributes, true),
				'isMultiple' => in_array(\CCrmFieldInfoAttr::Multiple, $attributes, true),
				'isDynamic' => in_array(\CCrmFieldInfoAttr::Dynamic, $attributes, true)
			];

			$field['settings'] = isset($fieldInfo['SETTINGS']) ? $fieldInfo['SETTINGS'] : [];

			if ($fieldType === 'enumeration')
			{
				$field['items'] = isset($fieldInfo['ITEMS']) ? $fieldInfo['ITEMS'] : [];
			}
			elseif ($fieldType === 'crm_status')
			{
				$field['statusType'] = isset($fieldInfo['CRM_STATUS_TYPE']) ? $fieldInfo['CRM_STATUS_TYPE'] : '';
			}
			elseif ($fieldType === 'product_property')
			{
				$field['propertyType'] = isset($fieldInfo['PROPERTY_TYPE']) ? $fieldInfo['PROPERTY_TYPE'] : '';
				$field['userType'] = isset($fieldInfo['USER_TYPE']) ? $fieldInfo['USER_TYPE'] : '';
				$field['title'] = isset($fieldInfo['NAME']) ? $fieldInfo['NAME'] : '';

				if ($field['propertyType'] === 'L')
				{
					$field['values'] = isset($fieldInfo['VALUES']) ? $fieldInfo['VALUES'] : [];
				}
			}

			if (isset($fieldInfo['LABELS']) && is_array($fieldInfo['LABELS']))
			{
				$labels = $fieldInfo['LABELS'];

				if (isset($labels['LIST']))
				{
					$field['listLabel'] = $labels['LIST'];
				}

				if (isset($labels['FORM']))
				{
					$field['formLabel'] = $labels['FORM'];
				}

				if (isset($labels['FILTER']))
				{
					$field['filterLabel'] = $labels['FILTER'];
				}
			}

			$result[$fieldId] = $field;
		}

		return $result;
	}

	protected static function prepareUserFieldsInfo(&$fieldsInfo, $entityTypeId)
	{
		$userType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entityTypeId);
		$userType->PrepareFieldsInfo($fieldsInfo);
	}

	protected static function prepareMultiFieldsInfo(&$fieldsInfo)
	{
		$typesId = array_keys(\CCrmFieldMulti::GetEntityTypeInfos());

		foreach ($typesId as $typeId)
		{
			$fieldsInfo[$typeId] = [
				'TYPE' => 'crm_multifield',
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Multiple]
			];
		}
	}

	protected static function prepareRequisitesInfo($entityName, &$fieldsInfo)
	{
		$presets = static::getRequisitePresetsInfo();

		$requisiteFields = static::getFieldsInternal(
			\CCrmOwnerType::RequisiteName,
			[
				'CLASS_NAME' => '\Bitrix\Crm\EntityRequisite',
				'HAS_USER_FIELDS' => true,
				'HAS_MULTI_FIELDS' => false,
				'HAS_REQUISITES' => false
			]
		);

		$addressFields = static::getFieldsInternal(
			'ADDRESS',
			[
				'CLASS_NAME' => '\Bitrix\Crm\RequisiteAddress',
				'HAS_USER_FIELDS' => false,
				'HAS_MULTI_FIELDS' => false,
				'HAS_REQUISITES' => false
			]
		);
		array_unshift($addressFields, [
			'type' => 'location',
			'entity_field_name' => 'LOCATION',
			'entity_name' => 'ADDRESS',
			'caption' => Loc::getMessage('CRM_ORDER_MATCHER_LOCATION'),
			'original_caption' => Loc::getMessage('CRM_ORDER_MATCHER_LOCATION'),
			'multiple' => false,
			'required' => false,
			'hidden' => false,
		]);
		$addressTypes = EntityAddressType::getDescriptions(EntityAddressType::getAvailableIds());

		$bankDetailFields = static::getFieldsInternal(
			'BANK_DETAIL',
			[
				'CLASS_NAME' => '\Bitrix\Crm\EntityBankDetail',
				'HAS_USER_FIELDS' => false,
				'HAS_MULTI_FIELDS' => false,
				'HAS_REQUISITES' => false
			]
		);

		$tree = [];

		foreach ($presets as $presetId => $preset)
		{
			$presetEntity = EntityPreset::getSingleInstance();
			$presetFields = $presetEntity->getById($presetId);

			$presetFieldsInfo = [];

			if (!empty($presetFields['SETTINGS']['FIELDS']) && is_array($presetFields['SETTINGS']['FIELDS']))
			{
				foreach ($presetFields['SETTINGS']['FIELDS'] as $field)
				{
					foreach ($requisiteFields as $requisiteField)
					{
						if ($field['FIELD_NAME'] === $requisiteField['entity_field_name'])
						{
							$requisiteField['name'] = $entityName.'_'.$requisiteField['entity_field_name'].'_'.$presetId;
							$requisiteField['preset_id'] = $presetId;

							$presetFieldsInfo[] = $requisiteField;
						}
					}
				}
			}

			if (!empty($addressFields))
			{
				$addressTypeInfo = [];

				foreach ($addressTypes as $addressTypeId => $addressTypeTitle)
				{
					$addressFieldsInfo = [];

					foreach ($addressFields as $addressField)
					{
						$addressField['name'] = $entityName.'_'.$addressField['entity_field_name'].'_'.$presetId.'_'.$addressTypeId;
						$addressField['preset_id'] = $presetId;
						$addressField['address'] = 'Y';
						$addressField['address_type'] = $addressTypeId;

						$addressFieldsInfo[] = $addressField;
					}

					$addressTypeInfo[] = [
						'type' => 'tree',
						'tree' => [
							'ADDRESS_TYPE_'.$addressTypeId => [
								'CAPTION' => $addressTypeTitle,
								'FIELDS' => $addressFieldsInfo
							]
						]
					];
				}

				$presetFieldsInfo[] = [
					'type' => 'tree',
					'tree' => [
						'ADDRESS' => [
							'CAPTION' => Loc::getMessage('CRM_ORDER_MATCHER_ADDRESS'),
							'FIELDS' => $addressTypeInfo
						]
					]
				];
			}

			if (!empty($bankDetailFields))
			{
				$presetBdFieldsInfo = [];

				foreach ($bankDetailFields as $bankDetailField)
				{
					$bankDetailField['name'] = $entityName.'_'.$bankDetailField['entity_field_name'].'_'.$presetId;
					$bankDetailField['preset_id'] = $presetId;
					$bankDetailField['bank_detail'] = 'Y';

					$presetBdFieldsInfo[] = $bankDetailField;
				}

				$presetFieldsInfo[] = [
					'type' => 'tree',
					'tree' => [
						'BANK_DETAIL' => [
							'CAPTION' => Loc::getMessage('CRM_ORDER_MATCHER_BANK_DETAIL'),
							'FIELDS' => $presetBdFieldsInfo
						]
					]
				];
			}

			if (!empty($presetFieldsInfo))
			{
				$tree[] = [
					'type' => 'tree',
					'tree' => [
						'RQ_PRESET_ID_'.$presetId => [
							'CAPTION' => $preset,
							'FIELDS' => $presetFieldsInfo
						]
					]
				];
			}
		}

		if (!empty($tree))
		{
			$fieldsInfo[\CCrmOwnerType::RequisiteName] = [
				'type' => 'tree',
				'tree' => [
					\CCrmOwnerType::RequisiteName => [
						'CAPTION' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Requisite),
						'FIELDS' => $tree
					]
				]
			];
		}
	}

	protected static function extractFieldsFromEntity($parentEntities, $entity)
	{
		$fields = [];

		foreach ($entity['FIELDS'] as $field)
		{
			if ($field['type'] === 'tree')
			{
				foreach ($field['tree'] as $treeName => $treeEntity)
				{
					$entities = $parentEntities;
					$entities[$treeName] = $treeEntity['CAPTION'];
					$fields = array_merge($fields, static::extractFieldsFromEntity($entities, $treeEntity));
				}
			}
			else
			{
				$field['entity_caption'] = reset($parentEntities);
				$field['entity_name'] = key($parentEntities);
				$field['entity_parents'] = $parentEntities;

				$fields[] = $field;
			}
		}

		return $fields;
	}

	public static function getFields($personTypeId)
	{
		$fields = [];

		$fieldsByEntity = static::getFieldsTree($personTypeId);

		foreach ($fieldsByEntity as $entityName => $entity)
		{
			$parentEntities = [$entityName => $entity['CAPTION']];
			$fields = array_merge($fields, static::extractFieldsFromEntity($parentEntities, $entity));
		}

		return $fields;
	}

	public static function getFieldsByCode($personTypeId)
	{
		$fields = static::getFields($personTypeId);

		return array_column($fields, null, 'name');
	}

	public static function prepareToDeactivate($field)
	{
		return [
			'ID' => $field['id'],
			'UTIL' => 'Y'
		];
	}

	public static function getCrmFieldType($property)
	{
		switch ($property['TYPE'])
		{
			case 'STRING':
			case 'NUMBER':
				$crmType = self::FIELD_TYPE_STRING;
				break;
			case 'Y/N':
				$crmType = self::FIELD_TYPE_CHECKBOX;
				break;
			case 'ENUM':
				if (isset($property['SETTINGS']['MULTIELEMENT']) && $property['SETTINGS']['MULTIELEMENT'] === 'Y')
				{
					$crmType = $property['MULTIPLE'] === 'Y' ? self::FIELD_TYPE_LIST_CHECKBOX : self::FIELD_TYPE_RADIO;
				}
				else
				{
					$crmType = self::FIELD_TYPE_LIST;
				}

				break;
			case 'DATE':
				$crmType = self::FIELD_TYPE_DATE;
				break;
			case 'FILE':
				$crmType = self::FIELD_TYPE_FILE;
				break;
			case 'LOCATION':
				$crmType = self::FIELD_TYPE_LOCATION;
				break;
			case 'ADDRESS':
				$crmType = self::FIELD_TYPE_ADDRESS;
				break;
			default:
				$crmType = self::FIELD_TYPE_STRING;
		}

		return $crmType;
	}

	public static function getSaleFieldType($crmType)
	{
		switch ($crmType)
		{
			case self::FIELD_TYPE_CHECKBOX:
				$saleType = 'Y/N'; break;
			case self::FIELD_TYPE_RADIO:
			case self::FIELD_TYPE_LIST:
			case self::FIELD_TYPE_LIST_CHECKBOX:
				$saleType = 'ENUM'; break;
			case self::FIELD_TYPE_DATE:
				$saleType = 'DATE'; break;
			case self::FIELD_TYPE_FILE:
				$saleType = 'FILE'; break;
			case self::FIELD_TYPE_LOCATION:
				$saleType = 'LOCATION'; break;
			case self::FIELD_TYPE_ADDRESS:
				$saleType = 'ADDRESS'; break;
			case self::FIELD_TYPE_STRING:
			case self::FIELD_TYPE_TEXT:
			default:
				$saleType = 'STRING';
		}

		return $saleType;
	}

	protected static function prepareSimilarFields($field)
	{
		$prepared = [
			'ACTIVE' => 'Y',
			'UTIL' => 'N',
			'TYPE' => static::getSaleFieldType($field['TYPE']),
			'NAME' => (string)$field['CAPTION'],
			'DEFAULT_VALUE' => is_array($field['VALUE']) ? $field['VALUE'] : (string)$field['VALUE'],
			'SORT' => (int)$field['SORT'],
			'DESCRIPTION' => (string)$field['PLACEHOLDER'],
			'ITEMS' => isset($field['ITEMS']) ? $field['ITEMS'] : []
		];

		$fieldsToUpdate = [
			'MULTIPLE', 'REQUIRED', 'USER_PROPS',
			'IS_PROFILE_NAME', 'IS_PAYER', 'IS_EMAIL', 'IS_PHONE', 'IS_ZIP', 'IS_ADDRESS'
		];

		foreach ($fieldsToUpdate as $fieldToUpdate)
		{
			if (isset($field[$fieldToUpdate]))
			{
				$prepared[$fieldToUpdate] = $field[$fieldToUpdate] === 'Y' ? 'Y' : 'N';
			}
		}

		return $prepared;
	}

	protected static function prepareToUpdate($field)
	{
		$similarFields = static::prepareSimilarFields($field);
		$similarFields['ID'] = $field['ID'];

		if (!empty($field['PROPS_GROUP_ID']))
		{
			$similarFields['PROPS_GROUP_ID'] = $field['PROPS_GROUP_ID'];
		}

		return $similarFields;
	}

	protected static function prepareToInsert($field, $personTypeId)
	{
		$similarFields = static::prepareSimilarFields($field);

		$similarFields['PERSON_TYPE_ID'] = $personTypeId;
		$similarFields['PROPS_GROUP_ID'] = !empty($field['PROPS_GROUP_ID']) ? $field['PROPS_GROUP_ID'] : static::getPropsGroupId($personTypeId);

		return $similarFields;
	}

	protected static function getPropsGroupId($personTypeId)
	{
		static $groupCache = [];

		if (empty($groupCache[$personTypeId]))
		{
			$orderProps = OrderPropsGroupTable::getRow([
				'select' => ['ID'],
				'filter' => ['=PERSON_TYPE_ID' => $personTypeId],
				'order' => ['SORT' => 'asc']
			]);

			if (!empty($orderProps))
			{
				$groupCache[$personTypeId] = $orderProps['ID'];
			}
		}

		return $groupCache[$personTypeId];
	}

	protected static function prepareRequisiteFields(&$prepared, $field)
	{
		$prepared['RQ_PRESET_ID'] = isset($field['RQ_PRESET_ID']) ? $field['RQ_PRESET_ID'] : 0;
		$prepared['RQ_BANK_DETAIL'] = isset($field['RQ_BANK_DETAIL']) ? $field['RQ_BANK_DETAIL'] : 'N';
		$prepared['RQ_ADDR'] = isset($field['RQ_ADDR']) ? $field['RQ_ADDR'] : 'N';
		$prepared['RQ_ADDR_TYPE'] = isset($field['RQ_ADDR_TYPE']) ? $field['RQ_ADDR_TYPE'] : 0;
	}

	public static function prepareFiles(&$prepared, $field)
	{
		if ($prepared['TYPE'] == 'FILE')
		{
			if (isset($prepared['DEFAULT_VALUE']['name']))
			{
				$prepared['DEFAULT_VALUE'] = [$prepared['DEFAULT_VALUE']];
			}

			$files = File::asMultiple($prepared['DEFAULT_VALUE']);

			foreach ($files as $i => $file)
			{
				if (File::isDeletedSingle($file))
				{
					unset($files[$i]);
				}
				else
				{
					if (
						File::isUploadedSingle($file)
						&& ($fileId = \CFile::SaveFile(['MODULE_ID' => 'sale'] + $file, 'sale/order/properties/default'))
						&& is_numeric($fileId)
					)
					{
						$file = $fileId;
					}

					$files[$i] = $file;
				}
			}

			$prepared['DEFAULT_VALUE'] = $prepared['MULTIPLE'] === 'Y' ? $files : reset($files);
		}
	}

	public static function getDeliveryRelations()
	{
		if (!isset(static::$relations['D']))
		{
			$deliveries = array();

			foreach (DeliveryManager::getActiveList(true) as $deliveryId => $deliveryFields)
			{
				$deliveries[$deliveryId] = [
					'ID' => $deliveryId,
					'VALUE' => $deliveryFields['NAME'].' ['.$deliveryId.']',
				];
			}

			static::$relations['D'] = $deliveries;
		}

		return static::$relations['D'];
	}

	public static function getPaySystemRelations()
	{
		if (!isset(static::$relations['P']))
		{
			$paySystems = [];

			$result = PaySystemManager::getList([
				'select' => ['ID', 'NAME', 'ACTIVE', 'SORT'],
				'filter' => ['ACTIVE' => 'Y'],
				'order' => ['SORT'=>'ASC', 'NAME'=>'ASC']
			]);
			while ($row = $result->fetch())
			{
				$paySystems[$row['ID']] = [
					'ID' => $row['ID'],
					'VALUE' => $row['NAME'].' ['.$row['ID'].']',
				];
			}

			static::$relations['P'] = $paySystems;
		}

		return static::$relations['P'];
	}

	private static function extractRelations(&$itemFields)
	{
		$relations = $itemFields['RELATIONS'] ?? [];
		unset($itemFields['RELATIONS']);

		return $relations;
	}

	private static function extractMatchProperties(&$itemFields)
	{
		$entityName = $itemFields['ENTITY_NAME'];
		unset($itemFields['ENTITY_NAME']);
		$matchEntityFieldCode = $itemFields['ENTITY_FIELD_CODE'];
		unset($itemFields['ENTITY_FIELD_CODE']);
		$requisitePresetId = (int)$itemFields['RQ_PRESET_ID'];
		unset($itemFields['RQ_PRESET_ID']);
		$bankDetail = $itemFields['RQ_BANK_DETAIL'];
		unset($itemFields['RQ_BANK_DETAIL']);
		$address = $itemFields['RQ_ADDR'];
		unset($itemFields['RQ_ADDR']);
		$addressType = $itemFields['RQ_ADDR_TYPE'];
		unset($itemFields['RQ_ADDR_TYPE']);

		if (empty($entityName) || $entityName === \CCrmOwnerType::OrderName)
		{
			return [];
		}

		if (\CCrmFieldMulti::IsSupportedType($matchEntityFieldCode))
		{
			$multiFieldType = $itemFields['MULTI_FIELD_TYPE'];
			unset($itemFields['MULTI_FIELD_TYPE']);

			$fieldType = BaseEntityMatcher::MULTI_FIELD_TYPE;
			$fieldCode = $matchEntityFieldCode.'_'.$multiFieldType;
			$settings = [];
		}
		elseif ($requisitePresetId)
		{
			$presets = static::getRequisitePresetsInfo();

			if ($bankDetail === 'Y')
			{
				$fieldType = BaseEntityMatcher::BANK_DETAIL_FIELD_TYPE;
				$fieldCode = $matchEntityFieldCode;
				$settings = [
					'RQ_NAME' => $presets[$requisitePresetId],
					'RQ_PRESET_ID' => $requisitePresetId,
					'BD_NAME' => Loc::getMessage('CRM_ORDER_MATCHER_BANK_DETAIL'),
					'BD_COUNTRY_ID' => static::getDefaultCountryId()
				];
			}
			elseif ($address === 'Y')
			{
				$fieldType = BaseEntityMatcher::REQUISITE_FIELD_TYPE;
				$fieldCode = 'RQ_ADDR';
				$settings = [
					'RQ_NAME' => $presets[$requisitePresetId],
					'RQ_PRESET_ID' => $requisitePresetId,
					'RQ_ADDR_TYPE' => $addressType,
					'RQ_ADDR_CODE' => $matchEntityFieldCode
				];
			}
			else
			{
				$fieldType = BaseEntityMatcher::REQUISITE_FIELD_TYPE;
				$fieldCode = $matchEntityFieldCode;
				$settings = [
					'RQ_NAME' => $presets[$requisitePresetId],
					'RQ_PRESET_ID' => $requisitePresetId
				];
			}
		}
		else
		{
			$fieldType = BaseEntityMatcher::GENERAL_FIELD_TYPE;
			$fieldCode = $matchEntityFieldCode;
			$settings = [];
		}

		return [
			'CRM_ENTITY_TYPE' => \CCrmOwnerType::ResolveID($entityName),
			'CRM_FIELD_TYPE' => $fieldType,
			'CRM_FIELD_CODE' => $fieldCode,
			'SETTINGS' => $settings
		];
	}

	private static function extractItems(&$itemFields)
	{
		$items = isset($itemFields['ITEMS']) ? $itemFields['ITEMS'] : [];
		unset($itemFields['ITEMS']);

		return $items;
	}

	private static function addProperties($items)
	{
		$result = new Result();

		foreach ($items as $itemFields)
		{
			$variants = static::extractItems($itemFields);
			$relations = static::extractRelations($itemFields);
			$matchProperties = static::extractMatchProperties($itemFields);

			/** @var \Bitrix\Main\Result $res */
			$res = OrderPropsTable::add($itemFields);

			if ($res->isSuccess())
			{
				$propertyId = $res->getId();

				if (!empty($variants))
				{
					foreach ($variants as $item)
					{
						$item['ORDER_PROPS_ID'] = $propertyId;
						unset($item['ID']);
						\CSaleOrderPropsVariant::Add($item);
					}
				}

				if (!empty($relations))
				{
					self::saveRelations($propertyId, $relations);
				}

				if (!empty($matchProperties))
				{
					OrderPropsMatchTable::add(['SALE_PROP_ID' => $propertyId] + $matchProperties);
				}
			}
			else
			{
				$result->addErrors($res->getErrors());
			}
		}

		return $result;
	}

	private static function updateProperties($items)
	{
		$result = new Result();

		foreach ($items as $itemFields)
		{
			$propertyId = $itemFields['ID'];
			unset($itemFields['ID']);

			$variants = static::extractItems($itemFields);
			$relations = static::extractRelations($itemFields);
			$matchProperties = static::extractMatchProperties($itemFields);

			$res = OrderPropsTable::update($propertyId, $itemFields);

			if ($res->isSuccess())
			{
				if (!empty($variants))
				{
					$existingItems = [];

					$existingItemsIterator = \CSaleOrderPropsVariant::GetList([], ['ORDER_PROPS_ID' => $propertyId], false, false, ['ID']);
					while ($existingItem = $existingItemsIterator->Fetch())
					{
						$existingItems[] = $existingItem['ID'];
					}

					foreach ($variants as $item)
					{
						foreach ($item as $key => $value)
						{
							if (is_string($key) && $key[0] === '~')
							{
								unset($item[$key]);
							}
						}

						if (in_array($item['ID'], $existingItems))
						{
							\CSaleOrderPropsVariant::Update($item['ID'], array_diff_key($item, ['ID' => 0]));
						}
						else
						{
							$item['ORDER_PROPS_ID'] = $propertyId;
							$item['VALUE'] = $item['ID'];
							\CSaleOrderPropsVariant::Add(array_diff_key($item, ['ID' => 0]));
						}
					}
				}

				if (!empty($relations))
				{
					self::saveRelations($propertyId, $relations);
				}

				if (!empty($matchProperties) || $itemFields['UTIL'] === 'Y')
				{
					$propertyMatch = OrderPropsMatchTable::getByPropertyId($propertyId);

					if (!empty($propertyMatch))
					{
						if ($itemFields['UTIL'] === 'Y')
						{
							OrderPropsMatchTable::delete($propertyMatch['ID']);
						}
						else
						{
							OrderPropsMatchTable::update($propertyMatch['ID'], $matchProperties);
						}
					}
					elseif (!empty($matchProperties))
					{
						OrderPropsMatchTable::add(['SALE_PROP_ID' => $res->getId()] + $matchProperties);
					}
				}
			}
			else
			{
				$result->addErrors($res->getErrors());
			}
		}

		return $result;
	}

	protected static function saveRelations($propertyId, $relations)
	{
		$existedRelation = OrderPropsRelationTable::getList([
			'select' => ['ENTITY_ID', 'ENTITY_TYPE'],
			'filter' => [
				'=PROPERTY_ID' => $propertyId,
				'@ENTITY_TYPE' => ['P', 'D']
			]
		]);
		foreach ($existedRelation as $item)
		{
			$index = array_search($item['ENTITY_ID'], $relations[$item['ENTITY_TYPE']]);
			if ($index === false)
			{
				OrderPropsRelationTable::delete([
					'PROPERTY_ID' => $propertyId,
					'ENTITY_ID' => $item['ENTITY_ID'],
					'ENTITY_TYPE' => $item['ENTITY_TYPE'],
				]);
			}
			else
			{
				unset($relations[$item['ENTITY_TYPE']][$index]);
			}
		}

		foreach ($relations as $entityType => $relation)
		{
			foreach ($relation as $entityId)
			{
				OrderPropsRelationTable::add([
					'PROPERTY_ID' => $propertyId,
					'ENTITY_ID' => $entityId,
					'ENTITY_TYPE' => $entityType,
				]);
			}
		}
	}

	public static function updateDuplicateMode($personTypeId, $duplicateMode)
	{
		$form = FormTable::getRow([
			'filter' => ['PERSON_TYPE_ID' => $personTypeId]
		]);

		if (!empty($form))
		{
			$result = FormTable::update($form['ID'], [
				'DUPLICATE_MODE' => $duplicateMode
			]);
		}
		else
		{
			$result = FormTable::add([
				'PERSON_TYPE_ID' => $personTypeId,
				'DUPLICATE_MODE' => $duplicateMode,
			]);
		}

		return $result;
	}

	/**
	 * Normalization request fields.
	 *
	 * Normalization includes:
	 * 1. merge an fields with short and full field codes.
	 * Example: in request exists fields `ORDER_PROPERTY_IDCODE` and `PROPERTY_IDCODE`,
	 * then the values of these fields will be combined into one element `PROPERTY_IDCODE` (not normal field name).
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	private static function getNormalizedFields(array $fields): array
	{
		$result = [];

		$doubles = [];
		foreach ($fields as $fieldCode => $field)
		{
			$normalizedName = self::getOrderPropertyName($fieldCode, $field['ID']);
			if (isset($result[$normalizedName]))
			{
				if ($normalizedName === $fieldCode)
				{
					$result[$normalizedName] = $field + $result[$normalizedName];
				}
				else
				{
					$result[$normalizedName] += $field;
				}
			}
			else
			{
				$result[$normalizedName] = $field;
			}

			if ($normalizedName !== $fieldCode)
			{
				$doubles[$fieldCode] = $normalizedName;
			}
		}

		// replace original names
		foreach ($doubles as $originalName => $normalizedName)
		{
			$result[$originalName] = $result[$normalizedName];
			unset($result[$normalizedName]);
		}

		return $result;
	}

	public static function save($personTypeId, $fields, $relations = [])
	{
		$result = new Result();

		$fields = static::getNormalizedFields($fields);

		list($itemsToCreate, $itemsToUpdate) = static::getFieldsToSynchronize($personTypeId, $fields, $relations);

		if (!empty($itemsToCreate))
		{
			$res = static::addProperties($itemsToCreate);
			if (!$res->isSuccess())
			{
				$result->addErrors($res->getErrors());
			}
		}

		if (!empty($itemsToUpdate))
		{
			$res = static::updateProperties($itemsToUpdate);
			if (!$res->isSuccess())
			{
				$result->addErrors($res->getErrors());
			}
		}

		static::clearFieldsCache();

		return $result;
	}

	protected static function getDefaultRelations()
	{
		return ['P' => [], 'D' => []];
	}

	protected static function parseRelations($relations)
	{
		$parsedRelations = [];

		$existingRelationEntities = [
			'P' => array_keys(static::getPaySystemRelations()),
			'D' => array_keys(static::getDeliveryRelations()),
		];

		foreach ($relations as $relation)
		{
			$code = $relation['DO_FIELD_CODE'];

			if (empty($code) || empty($existingRelationEntities[$relation['IF_FIELD_CODE']]))
			{
				continue;
			}

			if (!isset($parsedRelations[$code]) || !is_array($parsedRelations[$code]))
			{
				$parsedRelations[$code] = static::getDefaultRelations();
			}

			$relation['IF_VALUE'] = explode(',', $relation['IF_VALUE']);

			if ($relation['DO_ACTION'] === 'SHOW')
			{
				$parsedRelations[$code][$relation['IF_FIELD_CODE']] = array_unique(array_merge(
					$parsedRelations[$code][$relation['IF_FIELD_CODE']],
					$relation['IF_VALUE']
				));
			}
			elseif ($relation['DO_ACTION'] === 'HIDE')
			{
				$parsedRelations[$code][$relation['IF_FIELD_CODE']] = array_unique(array_merge(
					$parsedRelations[$code][$relation['IF_FIELD_CODE']],
					array_diff($existingRelationEntities[$relation['IF_FIELD_CODE']], $relation['IF_VALUE'])
				));
			}
		}

		return $parsedRelations;
	}

	protected static function getFieldsToSynchronize($personTypeId, $fields, $relations = [])
	{
		$fieldsToUpdate = [];
		$orderFields = static::getOrderFieldsDescription($personTypeId);

		foreach ($orderFields as $field)
		{
			if (!isset($fields[$field['name']]) && !isset($fields[$field['entity_field_name']]))
			{
				if ($field['active'] && !$field['util'])
				{
					$fieldsToUpdate[] = static::prepareToDeactivate($field);
				}
			}
		}

		$fieldsToCreate = [];
		$availableFields = static::getFieldsByCode($personTypeId);
		$relations = static::parseRelations($relations);

		foreach ($fields as $fieldCode => $field)
		{
			$propertyName = static::getOrderPropertyName($fieldCode, $field['ID']);

			if (isset($availableFields[$propertyName]))
			{
				$fieldInfo = $availableFields[$propertyName];
				$field['ID'] = $fieldInfo['id'];
			}
			elseif (isset($availableFields[$fieldCode]))
			{
				$fieldInfo = $availableFields[$fieldCode];
			}
			else
			{
				throw new SystemException("Cannot find available field with name '{$fieldCode}'");
			}

			if ($fieldInfo['entity_name'] === \CCrmOwnerType::OrderName)
			{
				$prepared = static::prepareToUpdate($field);

				if (isset($availableFields[$fieldCode]))
				{
					$prepared['ENTITY_NAME'] = $availableFields[$fieldCode]['entity_name'];
					$prepared['ENTITY_FIELD_CODE'] = $availableFields[$fieldCode]['entity_field_name'];
				}

				if (isset($field['VALUE_TYPE']))
				{
					$prepared['MULTI_FIELD_TYPE'] = $field['VALUE_TYPE'];
				}

				static::prepareFiles($prepared, $field);
				static::prepareRequisiteFields($prepared, $field);

				$prepared['RELATIONS'] =
					$relations[$fieldCode]
					?? $relations[ $fieldInfo['entity_field_name'] ] // short name, ex: ADDRESS
					?? $relations[ $propertyName ] // full name with prefix, ex: ORDER_ADDRESS
					?? static::getDefaultRelations()
				;

				$fieldsToUpdate[] = $prepared;
			}
			else
			{
				$prepared = static::prepareToInsert($field, $personTypeId);
				$prepared['CODE'] = $fieldInfo['name'];
				$prepared["ENTITY_REGISTRY_TYPE"] = \Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER;
				$prepared['ENTITY_NAME'] = $fieldInfo['entity_name'];
				$prepared['ENTITY_FIELD_CODE'] = $fieldInfo['entity_field_name'];

				if (
					empty($prepared['CODE'])
					|| empty($prepared['ENTITY_NAME'])
					|| empty($prepared['ENTITY_FIELD_CODE'])
				)
				{
					throw new SystemException("Not filled all entity info for field '{$fieldCode}'");
				}

				if (isset($field['VALUE_TYPE']))
				{
					$prepared['MULTI_FIELD_TYPE'] = $field['VALUE_TYPE'];
				}

				static::prepareFiles($prepared, $field);
				static::prepareRequisiteFields($prepared, $field);

				$prepared['RELATIONS'] = $relations[$fieldCode] ?? static::getDefaultRelations();

				$fieldsToCreate[] = $prepared;
			}
		}

		return [$fieldsToCreate, $fieldsToUpdate];
	}

	public static function onAfterSetEnumValues($ufId, $items = [])
	{
		if (!Loader::includeModule('sale') || empty($items))
			return;

		$userField = \Bitrix\Main\UserFieldTable::getRow([
			'select' => ['ENTITY_ID', 'FIELD_NAME'],
			'filter' => [
				'=ID' => $ufId,
				'=USER_TYPE_ID' => 'enumeration',
			]
		]);

		if (!empty($userField))
		{
			$matchedProperty = OrderPropsMatchTable::getRow([
				'select' => ['SALE_PROP_ID'],
				'filter' => [
					'=CRM_FIELD_CODE' => $userField['FIELD_NAME'],
					'=SALE_PROPERTY.TYPE' => 'ENUM'
				]
			]);

			if (!empty($matchedProperty['SALE_PROP_ID']))
			{
				$existingItems = [];

				$existingItemsIterator = \CSaleOrderPropsVariant::GetList(
					[],
					['ORDER_PROPS_ID' => $matchedProperty['SALE_PROP_ID']],
					false, false, ['ID', 'NAME', 'VALUE']
				);
				while ($existingItem = $existingItemsIterator->Fetch())
				{
					$existingItems[$existingItem['VALUE']] = $existingItem;
				}

				foreach ($items as $id => $item)
				{
					if (isset($existingItems[$id]))
					{
						$variantId = $existingItems[$id]['ID'];

						if (isset($item['DEL']) && $item['DEL'] === 'Y')
						{
							// delete it in the end
						}
						else
						{
							\CSaleOrderPropsVariant::Update($variantId, [
								'NAME' => $item['VALUE'],
								'VALUE' => $id,
								'SORT' => $item['SORT'],
							]);
							unset($existingItems[$id]);
						}
					}
					else
					{
						\CSaleOrderPropsVariant::Add([
							'ORDER_PROPS_ID' => $matchedProperty['SALE_PROP_ID'],
							'NAME' => $item['VALUE'],
							'VALUE' => $id,
							'SORT' => $item['SORT']
						]);
					}
				}

				foreach ($existingItems as $existingItem)
				{
					\CSaleOrderPropsVariant::Delete($existingItem['ID']);
				}
			}
		}
	}
}

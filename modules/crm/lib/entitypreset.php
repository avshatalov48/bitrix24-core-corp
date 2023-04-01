<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Order\Matcher\BaseEntityMatcher;
use Bitrix\Crm\Order\Matcher\Internals\OrderPropsMatchTable;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class EntityPreset
{
	public const Undefined = 0;
	public const Requisite = 8;    // refresh FirstEntityType and LastEntityType constants (see the CCrmOwnerType constants)
	public const FirstEntityType = 8;
	public const LastEntityType = 8;

	public const NO_ERRORS = 0;
	public const ERR_DELETE_PRESET_USED = 1;
	public const ERR_PRESET_NOT_FOUND = 2;
	public const ERR_INVALID_ENTITY_TYPE = 3;

	protected const CACHE_PATH = '/crm/entitypreset/';
	protected const CACHE_TTL = 86400;
	protected const CACHE_ID_LIST_FOR_REQUISITE_ENTITY_EDITOR = 'listForRequisiteEntityEditor';
	protected const CACHE_ID_ACTIVE_ITEM_LIST = 'activeItemList';

	private static $singleInstance = null;

	private static $countryCodes = null;
	private static $countryInfo = null;
	private static $countryList = null;
	private static $fieldInfo = null;

	private static $staticCache = [];

	public static function getSingleInstance()
	{
		if (self::$singleInstance === null)
			self::$singleInstance = new EntityPreset();

		return self::$singleInstance;
	}

	public static function getEntityTypes()
	{
		$entityTypes = Array(
			self::Requisite => array(
				'CODE' => 'CRM_REQUISITE',
				'NAME' => GetMessage('CRM_ENTITY_TYPE_REQUISITE'),
				'DESC' => GetMessage('CRM_ENTITY_TYPE_REQUISITE_DESC')
			)
		);
		return $entityTypes;
	}

	public static function getUserFieldTypes()
	{
		$result = Array(
			'string' => array('ID' =>'string', 'NAME' => Loc::getMessage('CRM_ENTITY_PRESET_UF_TYPE_STRING')),
			'double' => array('ID' =>'double', 'NAME' => Loc::getMessage('CRM_ENTITY_PRESET_UF_TYPE_DOUBLE')),
			'boolean' => array('ID' =>'boolean', 'NAME' => Loc::getMessage('CRM_ENTITY_PRESET_UF_TYPE_BOOLEAN')),
			'datetime' => array('ID' =>'datetime', 'NAME' => Loc::getMessage('CRM_ENTITY_PRESET_UF_TYPE_DATETIME'))
		);

		return $result;
	}

	public static function checkEntityType($entityTypeId)
	{
		if(!is_numeric($entityTypeId))
			return false;

		$entityTypeId = intval($entityTypeId);

		return $entityTypeId >= self::FirstEntityType && $entityTypeId <= self::LastEntityType;
	}

	public static function getCurrentCountryId()
	{
		return (int)\COption::GetOptionInt("crm", "crm_requisite_preset_country_id", 0);
	}

	public static function setCurrentCountryId($countryId)
	{
		\COption::SetOptionInt("crm", "crm_requisite_preset_country_id", $countryId);
	}

	public static function getCountriesInfo()
	{
		if (self::$countryInfo === null)
		{
			$countryInfo = array();

			if (self::$countryCodes === null)
			{
				include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/countries.php");
				/** @var array $arCounries */
				self::$countryCodes = array_flip($arCounries);
			}

			foreach (self::getCountryList() as $id => $title)
			{
				$countryInfo[$id] = array(
					'CODE' => isset(self::$countryCodes[$id]) ? self::$countryCodes[$id] : '',
					'TITLE' => $title
				);
			}

			self::$countryInfo = $countryInfo;
		}

		return self::$countryInfo;
	}

	public static function getCountryList()
	{
		if (self::$countryList === null)
		{
			$countryList = array();
			$countries = GetCountryArray();
			if (isset($countries['reference_id'])
				&& isset($countries['reference'])
				&& is_array($countries['reference_id'])
				&& is_array($countries['reference']))
			{
				$refId = &$countries['reference_id'];
				$ref = &$countries['reference'];
				foreach ($ref as $id => $val)
					$countryList[$refId[$id]] = $val;
			}

			self::$countryList = $countryList;
		}

		return self::$countryList;
	}

	public static function getCountryCodeById($countryId)
	{
		$countryId = (int)$countryId;

		if (self::$countryCodes === null)
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/countries.php");
			/** @var array $arCounries */
			self::$countryCodes = array_flip($arCounries);
		}

		if(isset(self::$countryCodes[$countryId]))
			return self::$countryCodes[$countryId];

		return '';
	}

	public static function getEntityTypeCode($entityTypeId)
	{
		$entityTypeId = (int)$entityTypeId;
		$types = self::getEntityTypes();

		return isset($types[$entityTypeId]['CODE']) ? $types[$entityTypeId]['CODE'] : '';
	}

	public static function isUTFMode()
	{
		if (Option::get('crm', 'entity_preset_force_utf_mode', 'N') === 'Y')
			return true;

		if (defined('BX_UTF') && BX_UTF)
			return true;

		return false;
	}

	public function getFields()
	{
		return PresetTable::getMap();
	}

	public static function getActiveItemList()
	{
		$results = [];

		if (isset(static::$staticCache[static::CACHE_ID_ACTIVE_ITEM_LIST]))
		{
			$results = static::$staticCache[static::CACHE_ID_ACTIVE_ITEM_LIST];
		}
		else
		{
			$cache = Cache::createInstance();
			if (
				$cache->initCache(
					static::CACHE_TTL,
					static::CACHE_ID_ACTIVE_ITEM_LIST,
					static::CACHE_PATH
				)
			)
			{
				$results = $cache->getVars();
			}
			elseif ($cache->startDataCache())
			{
				$entity = self::getSingleInstance();
				$dbResult = $entity->getList(
					array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => self::getActivePresetFilter(),
						'select' => array('ID', 'NAME')
					)
				);

				$results = [];
				while ($fields = $dbResult->fetch())
				{
					$results[$fields['ID']] = $fields['NAME'];
				}

				$cache->endDataCache($results);
			}

			static::$staticCache[static::CACHE_ID_ACTIVE_ITEM_LIST] = $results;
		}

		return $results;
	}

	public static function getListForRequisiteEntityEditor()
	{
		$results = [];

		if (isset(static::$staticCache[static::CACHE_ID_LIST_FOR_REQUISITE_ENTITY_EDITOR]))
		{
			$results = static::$staticCache[static::CACHE_ID_LIST_FOR_REQUISITE_ENTITY_EDITOR];
		}
		else
		{
			$cache = Cache::createInstance();
			if (
				$cache->initCache(
					static::CACHE_TTL,
					static::CACHE_ID_LIST_FOR_REQUISITE_ENTITY_EDITOR,
					static::CACHE_PATH
				)
			)
			{
				$results = $cache->getVars();
			}
			elseif ($cache->startDataCache())
			{
				$results = [];

				$entity = self::getSingleInstance();
				$dbResult = $entity->getList(
					array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => self::getActivePresetFilter(),
						'select' => array('ID', 'NAME', 'COUNTRY_ID')
					)
				);
				while ($fields = $dbResult->fetch())
				{
					$results[$fields['ID']] = $fields;
				}

				$cache->endDataCache($results);
			}

			static::$staticCache[static::CACHE_ID_LIST_FOR_REQUISITE_ENTITY_EDITOR] = $results;
		}

		return $results;
	}

	public static function getDefault()
	{
		$entity = self::getSingleInstance();
		$dbResult = $entity->getList(
			array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'filter' => self::getActivePresetFilter(),
				'select' => array('ID', 'NAME'),
				'limit' => 1,
				'cache' => ['ttl' => 3600],
			)
		);
		if ($preset = $dbResult->fetch())
		{
			return $preset;
		}
		return null;
	}

	protected static function getActivePresetFilter()
	{
		$presetFilter = array(
			'=ENTITY_TYPE_ID' => EntityPreset::Requisite,
			'=ACTIVE' => 'Y',
		);

		if (!EntityPreset::isUTFMode())
		{
			$presetFilter['=COUNTRY_ID'] = EntityPreset::getCurrentCountryId();
		}
		return $presetFilter;
	}

	// Get Fields Metadata
	public static function getFieldsInfo()
	{
		if(!self::$fieldInfo)
		{
			self::$fieldInfo = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'ENTITY_TYPE_ID' => array(
						'TYPE' => 'integer',
						'ATTRIBUTES' => array(
								\CCrmFieldInfoAttr::Required,
								\CCrmFieldInfoAttr::Immutable)
				),
				'COUNTRY_ID' => array(
						'TYPE' => 'integer',
						'ATTRIBUTES' => array(
								\CCrmFieldInfoAttr::Required,
								\CCrmFieldInfoAttr::Immutable)
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required)
				),
				'DATE_CREATE' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_MODIFY' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'CREATED_BY_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'MODIFY_BY_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'ACTIVE' => array(
					'TYPE' => 'char'
				),
				'SORT' => array(
					'TYPE' => 'integer'
				),
				'XML_ID' => array(
					'TYPE' => 'string'
				)
			);
		}

		return self::$fieldInfo;
	}

	public static function getFieldCaption($fieldName)
	{
		$result = Loc::getMessage("CRM_ENTITY_PRESET_{$fieldName}_FIELD");
		return is_string($result) ? $result : '';
	}

	public static function getSettingsFieldsRestInfo()
	{
		return array(
			'ID' => array(
				'TYPE' => 'integer',
				'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
			),
			'FIELD_NAME' => array(
				'TYPE' => 'string',
				'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required)
			),
			'FIELD_TITLE' => array(
				'TYPE' => 'string'
			),
			'SORT' => array(
				'TYPE' => 'integer'
			),
			'IN_SHORT_LIST' => array(
				'TYPE' => 'char'
			)
		);
	}

	public function getSettingsFieldsInfo()
	{
		return array(
			'ID' => array('data_type' => 'integer'),
			'FIELD_NAME' => array('data_type' => 'string'),
			'FIELD_TITLE' => array('data_type' => 'string'),
			'SORT' => array('data_type' => 'integer'),
			'IN_SHORT_LIST' => array('data_type' => 'boolean')
		);
	}

	public function getSettingsFieldsAvailableToAdd($entityTypeId, $presetId)
	{
		$result = new Main\Result();

		if (!self::checkEntityType($entityTypeId))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_ENTITY_PRESET_ERR_INVALID_ENTITY_TYPE'),
					self::ERR_INVALID_ENTITY_TYPE
				)
			);
			return $result;
		}

		$requisite = new EntityRequisite();
		$presetId = (int)$presetId;
		$presetData = null;
		if ($presetId > 0)
			$presetData = $this->getById($presetId);
		if (!is_array($presetData))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_ENTITY_PRESET_ERR_PRESET_NOT_FOUND'),
					self::ERR_PRESET_NOT_FOUND
				)
			);
			return $result;
		}
		$presetCountryId = isset($presetData['COUNTRY_ID']) ? (int)$presetData['COUNTRY_ID'] : 0;

		$presetFields = array();
		if (is_array($presetData['SETTINGS']))
		{
			$fields = $this->settingsGetFields($presetData['SETTINGS']);
			if (!empty($fields))
			{
				foreach ($fields as $fieldInfo)
					$presetFields[$fieldInfo['FIELD_NAME']] = true;
			}
		}

		$availableFields = array();
		foreach ($requisite->getRqFieldsCountryMap() as $fieldName => $countries)
		{
			if (in_array($presetCountryId, $countries, true) && !isset($presetFields[$fieldName]))
				$availableFields[$fieldName] = true;
		}
		foreach ($requisite->getUserFields() as $fieldName)
		{
			if (!isset($presetFields[$fieldName]))
				$availableFields[$fieldName] = true;
		}

		$result->setData(array_keys($availableFields));

		return $result;
	}

	public function getList($params)
	{
		return PresetTable::getList($params);
	}

	public function getCountByFilter($filter = array())
	{
		return PresetTable::getCountByFilter($filter);
	}

	public function getById($id)
	{
		$result = PresetTable::getByPrimary($id);
		$row = $result->fetch();

		return (is_array($row)? $row : null);
	}

	public function clearCache()
	{
		Cache::clearCache(true, static::CACHE_PATH);
		static::$staticCache = [];
	}

	public function add($fields, $options = array())
	{
		unset($fields['ID'], $fields['DATE_MODIFY'], $fields['MODIFY_BY_ID']);
		$fields['DATE_CREATE'] = new DateTime();
		$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		if (isset($fields['SETTINGS']))
		{
			$fields['SETTINGS'] = $this->settingsPrepareFieldsBeforeSave($fields['SETTINGS']);
		}

		$this->clearCache();

		return PresetTable::add($fields);
	}

	public function update($id, $fields, $options = array())
	{
		unset($fields['DATE_CREATE'], $fields['CREATED_BY_ID']);
		$fields['DATE_MODIFY'] = new DateTime();
		$fields['MODIFY_BY_ID '] = \CCrmSecurityHelper::GetCurrentUserID();

		if (isset($fields['SETTINGS']))
		{
			$fields['SETTINGS'] = $this->settingsPrepareFieldsBeforeSave($fields['SETTINGS']);
		}

		$this->clearCache();

		return PresetTable::update($id, $fields);
	}

	public function delete($id, $options = array())
	{
		$id = (int)$id;
		$row = $this->getList(
			array(
				'filter' => array('=ID' => $id),
				'select' => array('ENTITY_TYPE_ID')
			)
		)->fetch();
		if (is_array($row) && isset($row['ENTITY_TYPE_ID']))
		{
			$entityTypeId = (int)$row['ENTITY_TYPE_ID'];

			if ($entityTypeId === self::Requisite)
			{
				$defForCompanyPresetId = EntityRequisite::getDefaultPresetId(\CCrmOwnerType::Company);
				$defForContactPresetId = EntityRequisite::getDefaultPresetId(\CCrmOwnerType::Contact);
				if ($id === $defForCompanyPresetId || $id === $defForContactPresetId)
				{
					$errMsg = GetMessage(
						'CRM_ENTITY_PRESET_ERR_DELETE_PRESET_DEF_FOR_'.
						($id === $defForCompanyPresetId ? 'COMPANY' : 'CONTACT')
					);
					$result = new Entity\DeleteResult();
					$result->addError(new Main\Error($errMsg, self::ERR_DELETE_PRESET_USED));
					return $result;
				}

				$requisite = new EntityRequisite();
				$row = $requisite->getList(
					array(
						'filter' => array('=PRESET_ID' => $id),
						'select' => array('ID'),
						'limit' => 1
					)
				)->fetch();
				if (is_array($row))
				{
					$result = new Entity\DeleteResult();
					$result->addError(
						new Main\Error(
							GetMessage('CRM_ENTITY_PRESET_ERR_DELETE_PRESET_USED'),
							self::ERR_DELETE_PRESET_USED
						)
					);
					return $result;
				}
			}
		}

		$this->clearCache();

		return PresetTable::delete($id);
	}

	public function extractFieldNames(array $settings)
	{
		$results = array();
		foreach($settings as $field)
		{
			if(isset($field['FIELD_NAME']))
			{
				$results[] = $field['FIELD_NAME'];
			}
		}
		return $results;
	}

	public function settingsGetFields(array $settings)
	{
		return (isset($settings['FIELDS']) && is_array($settings['FIELDS']) ? $settings['FIELDS'] : array());
	}

	public function settingsAddField(&$settings, $field)
	{
		if (!is_array($settings) || !is_array($field) || empty($field)
			|| !isset($field['FIELD_NAME']) || empty($field['FIELD_NAME']))
		{
			return false;
		}

		$maxId = 0;
		if (isset($settings['LAST_FIELD_ID']))
		{
			$maxId = (int)$settings['LAST_FIELD_ID'];
		}
		else
		{
			if (is_array($settings['FIELDS']))
			{
				foreach ($settings['FIELDS'] as $field)
				{
					$curId = (int)$field['ID'];
					if ($curId > $maxId)
						$maxId = $curId;
				}
			}
		}
		$id = $maxId + 1;

		$newField = array();
		$newField['ID'] = $id;
		$newField['FIELD_NAME'] = '';
		if (isset($field['FIELD_NAME']))
		{
			$newField['FIELD_NAME'] = mb_substr(strval($field['FIELD_NAME']), 0, 255);
			if ($newField['FIELD_NAME'] === false)
				$newField['FIELD_NAME'] = '';
		}
		$newField['FIELD_TITLE'] = '';
		if (isset($field['FIELD_TITLE']))
		{
			$newField['FIELD_TITLE'] = mb_substr(strval($field['FIELD_TITLE']), 0, 255);
			if ($newField['FIELD_TITLE'] === false)
				$newField['FIELD_TITLE'] = '';
		}
		$newField['IN_SHORT_LIST'] = 'N';
		if (isset($field['IN_SHORT_LIST'])
			&& $field['IN_SHORT_LIST'] === 'Y')
		{
			$newField['IN_SHORT_LIST'] = 'Y';
		}
		$newField['SORT'] = 500;
		if (isset($field['SORT']))
			$newField['SORT'] = (int)$field['SORT'];

		if (!is_array($settings['FIELDS']))
			$settings['FIELDS'] = array();

		$duplicate = false;
		foreach ($settings['FIELDS'] as $fieldInfo)
		{
			if ($fieldInfo['FIELD_NAME'] === $newField['FIELD_NAME'])
			{
				$duplicate = true;
				break;
			}
		}
		unset($fieldInfo);
		if ($duplicate)
			return false;

		$settings['LAST_FIELD_ID'] = $id;
		$settings['FIELDS'][] = $newField;

		return $id;
	}

	public function settingsUpdateField(&$settings, $field, $fieldIndex = null)
	{
		if (!is_array($settings) || !is_array($settings['FIELDS']) || !is_array($field) || empty($field)
			|| !isset($field['ID']) || intval($field['ID']) <= 0
			|| (isset($field['FIELD_NAME']) && empty($field['FIELD_NAME'])))
		{
			return false;
		}
		$id = (int)$field['ID'];
		if ($fieldIndex === null)
		{
			foreach ($settings['FIELDS'] as $index => $fieldData)
			{
				if (isset($fieldData['ID']) && intval($fieldData['ID']) === $id)
					$fieldIndex = $index;
			}
			unset($index, $fieldData);
		}
		if ($fieldIndex === null || $id !== intval($settings['FIELDS'][$fieldIndex]['ID']))
			return false;
		unset($id);

		$numberOfModified = 0;
		foreach ($field as $fieldName => $fieldValue)
		{
			$value = null;
			$fieldModified = true;
			if ($fieldName === 'FIELD_NAME' || $fieldName === 'FIELD_TITLE')
			{
				$value = mb_substr(strval($fieldValue), 0, 255);
				if ($value === false)
					$value = '';
			}
			else if ($fieldName === 'IN_SHORT_LIST')
			{
				$value = ($fieldValue === 'Y') ? 'Y' : 'N';
			}
			else if ($fieldName === 'SORT')
			{
				$value = (int)$fieldValue;
			}
			else
			{
				$fieldModified = false;
			}

			if ($fieldModified)
			{
				$settings['FIELDS'][$fieldIndex ][$fieldName] = $value;
				$numberOfModified++;
			}
		}

		if ($numberOfModified <= 0)
			return false;

		return true;
	}

	public function settingsDeleteField(&$settings, $id, $fieldIndex = null)
	{
		$id = (int)$id;
		if (!is_array($settings) || !is_array($settings['FIELDS']) || $id <= 0)
			return false;
		if ($fieldIndex === null)
		{
			foreach ($settings['FIELDS'] as $index => $fieldData)
			{
				if (isset($fieldData['ID']) && intval($fieldData['ID']) === $id)
					$fieldIndex = intval($index);
			}
			unset($index, $fieldData);
		}
		if ($fieldIndex === null || $id !== intval($settings['FIELDS'][$fieldIndex]['ID']))
			return false;
		unset($id);

		unset($settings['FIELDS'][$fieldIndex]);

		if (empty($settings['FIELDS']))
			$settings['LAST_FIELD_ID'] = 0;

		return true;
	}

	protected function settingsPrepareFieldsBeforeSave($settings)
	{
		$result = $settings;

		if (is_array($settings['FIELDS']) && !empty($settings['FIELDS']))
		{
			$fields = [];
			$fieldIdMap = [];
			$fieldNameMap = [];
			$lastFieldId = 0;
			$fieldsModified = false;
			$lastFieldIdModified = false;
			$settingsLastFieldId = isset($settings['LAST_FIELD_ID']) ? (int)$settings['LAST_FIELD_ID'] : 0;
			foreach ($settings['FIELDS'] as $fieldInfo)
			{
				if (isset($fieldInfo['ID'])
					&& $fieldInfo['ID'] > 0
					&& isset($fieldInfo['FIELD_NAME'])
					&& is_string($fieldInfo['FIELD_NAME'])
					&& $fieldInfo['FIELD_NAME'] !== '')
				{
					$fieldId = (int)$fieldInfo['ID'];
					$fieldName = $fieldInfo['FIELD_NAME'];
					if (!isset($fieldNameMap[$fieldName]))
					{
						if (isset($fieldIdMap[$fieldId]))
						{
							$fieldId = $lastFieldId + 1;
							$fieldInfo['ID'] = $fieldId;
							$fieldsModified = true;
						}
						$fieldIdMap[$fieldId] = true;
						$fieldNameMap[$fieldName] = true;
						$fields[] = $fieldInfo;
						if ($fieldId > $lastFieldId)
						{
							$lastFieldId = $fieldId;
						}
					}
					else
					{
						$fieldsModified = true;
					}
				}
				else
				{
					$fieldsModified = true;
				}
			}
			if ($lastFieldId === 0 || $lastFieldId > $settingsLastFieldId)
			{
				$lastFieldIdModified = true;
			}
			if ($fieldsModified)
			{
				$result['FIELDS'] = $fields;
			}
			if ($lastFieldIdModified)
			{
				$result['LAST_FIELD_ID'] = $lastFieldId;
			}
		}

		return $result;
	}

	public function getSettingsFieldsOfPresets($entityTypeId, $type = 'all', $options = array())
	{
		$result = array();

		if (!is_array($options))
			$options = array();

		$arrangeByCountry = false;
		if (isset($options['ARRANGE_BY_COUNTRY'])
			&& ($options['ARRANGE_BY_COUNTRY'] === true
				|| mb_strtoupper(strval($options['ARRANGE_BY_COUNTRY'])) === 'Y'))
		{
			$arrangeByCountry = true;
		}

		$filterByCountryIds = array();
		if (isset($options['FILTER_BY_COUNTRY_IDS']))
		{
			if (!is_array($options['FILTER_BY_COUNTRY_IDS']))
			{
				$filterByCountryIds = array((int)$options['FILTER_BY_COUNTRY_IDS']);
			}
			else
			{
				foreach ($options['FILTER_BY_COUNTRY_IDS'] as $id)
					$filterByCountryIds[] = (int)$id;
			}
			$arrangeByCountry = true;
		}
		$filterByCountry = !empty($filterByCountryIds);

		$filterByPresetIds = array();
		if (isset($options['FILTER_BY_PRESET_IDS']))
		{
			if (!is_array($options['FILTER_BY_PRESET_IDS']))
			{
				$filterByPresetIds = array((int)$options['FILTER_BY_PRESET_IDS']);
			}
			else
			{
				foreach ($options['FILTER_BY_PRESET_IDS'] as $id)
					$filterByPresetIds[] = (int)$id;
			}
		}
		$filterByPreset = !empty($filterByPresetIds);

		$filter = array('=ENTITY_TYPE_ID' => $entityTypeId);

		switch ($type)
		{
			case 'all':
				break;

			case 'active':
				$filter['=ACTIVE'] = 'Y';
				break;

			case 'inactive':
				$filter['=ACTIVE'] = 'N';
				break;
		}

		if ($this->checkEntityType($entityTypeId))
		{
			$fieldsAllowed = array();
			if ($entityTypeId === self::Requisite)
			{
				$requisite = new EntityRequisite();
				$fieldsAllowed = array_merge($requisite->getRqFields(), $requisite->getUserFields());
				$fieldsAllowed = array_diff($fieldsAllowed, EntityRequisite::getFileFields());
				unset($requisite);
			}

			$iResult = array();

			$select = array('ID');
			if ($arrangeByCountry)
				$select[] = 'COUNTRY_ID';
			$select[] =  'SETTINGS';

			if ($filterByCountry)
				$filter['=COUNTRY_ID'] = $filterByCountryIds;

			if ($filterByPreset)
				$filter['=ID'] = $filterByPresetIds;

			$res = $this->getList(array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'filter' => $filter,
				'select' => $select
			));
			while ($row = $res->fetch())
			{
				if (is_array($row['SETTINGS']))
				{
					$fields = $this->settingsGetFields($row['SETTINGS']);
					if (!empty($fields) && (!$arrangeByCountry || isset($row['COUNTRY_ID'])))
					{
						$countryId = (int)$row['COUNTRY_ID'];
						if (empty($filterByCountryIds) || in_array($countryId, $filterByCountryIds, true))
						{
							foreach ($fields as $fieldInfo)
							{
								if ($arrangeByCountry)
								{
									if ($countryId > 0)
									{
										if (isset($fieldInfo['FIELD_NAME'])
											&& !isset($iResult[$countryId][$fieldInfo['FIELD_NAME']]))
										{
											$iResult[$countryId][$fieldInfo['FIELD_NAME']] = true;
										}
									}
								}
								else
								{

									if (isset($fieldInfo['FIELD_NAME']) && !isset($iResult[$fieldInfo['FIELD_NAME']]))
										$iResult[$fieldInfo['FIELD_NAME']] = true;
								}
							}
						}
					}
				}
			}
			if ($arrangeByCountry)
			{
				$countryIds = array_keys($iResult);
				$includeZeroCountry = in_array(0, $filterByCountryIds, true);

				foreach ($fieldsAllowed as $fieldName)
				{
					if (!($filterByCountry || $filterByPreset) || $includeZeroCountry)
						$result[0][] = $fieldName;

					foreach ($countryIds as $countryId)
					{
						if (isset($iResult[$countryId][$fieldName]))
							$result[$countryId][] = $fieldName;
					}
				}
			}
			else
			{
				foreach ($fieldsAllowed as $fieldName)
				{
					if (isset($iResult[$fieldName]))
						$result[] = $fieldName;
				}
			}
			unset($iResult);
		}

		return $result;
	}

	public static function checkCreatePermissionOwnerEntity($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if($entityTypeID === \CCrmOwnerType::Requisite)
		{
			return (EntityRequisite::checkCreatePermissionOwnerEntity(\CCrmOwnerType::Company) &&
					EntityRequisite::checkCreatePermissionOwnerEntity(\CCrmOwnerType::Contact));
		}
		return false;
	}

	public static function checkUpdatePermissionOwnerEntity($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if($entityTypeID === \CCrmOwnerType::Requisite)
		{
			return (EntityRequisite::checkUpdatePermissionOwnerEntity(\CCrmOwnerType::Company, 0) &&
					EntityRequisite::checkUpdatePermissionOwnerEntity(\CCrmOwnerType::Contact, 0));
		}
		return false;
	}

	public static function checkDeletePermissionOwnerEntity($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if($entityTypeID === \CCrmOwnerType::Requisite)
		{
			return (EntityRequisite::checkDeletePermissionOwnerEntity(\CCrmOwnerType::Company, 0) &&
					EntityRequisite::checkDeletePermissionOwnerEntity(\CCrmOwnerType::Contact, 0));
		}
		return false;
	}

	public static function checkReadPermissionOwnerEntity()
	{
		return EntityRequisite::checkReadPermissionOwnerEntity();
	}

	/**
	 * @param \CCrmPerms $userPermissions
	 * @return bool
	 */
	public static function checkCreatePermission($userPermissions = null)
	{
		if ($userPermissions === null)
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();

		if ($userPermissions instanceof \CCrmPerms ||
			!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param \CCrmPerms $userPermissions
	 * @return bool
	 */
	public static function checkUpdatePermission($userPermissions = null)
	{
		if ($userPermissions === null)
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();

		if ($userPermissions instanceof \CCrmPerms && $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param \CCrmPerms $userPermissions
	 * @return bool
	 */
	public static function checkDeletePermission($userPermissions = null)
	{
		if ($userPermissions === null)
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();

		if ($userPermissions instanceof \CCrmPerms && $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param \CCrmPerms $userPermissions
	 * @return bool
	 */
	public static function checkReadPermission($userPermissions = null)
	{
		return true;
	}

	/**
	 * @param \CCrmPerms $userPermissions
	 * @return bool
	 */
	public static function checkChangeCurrentCountryPermission($userPermissions = null)
	{
		if ($userPermissions === null)
		{
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		if (
			$userPermissions instanceof \CCrmPerms
			&& $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE')
		)
		{
			return true;
		}

		return false;
	}

	public static function formatName($id, $title)
	{
		$title = trim(strval($title));
		if (empty($title))
			$title = '['.$id.'] - '.GetMessage('CRM_ENTITY_PRESET_NAME_EMPTY');

		return $title;
	}

	public static function getByXmlId($xmlId)
	{
		$preset = PresetTable::getList([
			'select' => ['ID'],
			'filter' => ['=XML_ID' => $xmlId],
			'cache' => ['ttl' => 3600]
		])->fetch();
		return $preset ? $preset['ID'] : false;
	}

	public function checkNeedChangeCurrentCountry(): bool
	{
		$countryId = (int)Option::get('crm', '~crm_requisite_current_country_can_change', 0);
		return (
			$countryId > 0
			&& in_array($countryId, EntityRequisite::getAllowedRqFieldCountries(), true)
			&& $countryId !== static::getCurrentCountryId()
			&& static::checkChangeCurrentCountryPermission()
		);
	}

	public function changeCurrentCountry(int $countryId): Main\Result
	{
		$result = new Main\Result();

		if ($countryId <= 0 || !in_array($countryId, EntityRequisite::getAllowedRqFieldCountries(), true))
		{
			$result->addError(new Main\Error("Incorrect country for change (ID: $countryId)"));
		}

		if ($result->isSuccess())
		{
			if (!static::checkChangeCurrentCountryPermission())
			{
				$result->addError(new Main\Error('Access denied!'));
			}
		}

		if ($result->isSuccess())
		{
			$currentCountryId = static::getCurrentCountryId();

			if ($currentCountryId > 0)
			{
				//region Delete all default presets for which there are no requisites.
				$fixedPresetList = EntityRequisite::getFixedPresetList();
				$defPresetsXmlIds = [];
				foreach ($fixedPresetList as $presetInfo)
				{
					$defPresetsXmlIds[] = $presetInfo['XML_ID'];
				}
				unset($presetInfo);

				$existsDefPresetMap = [];
				$existsDefPresetsByXmlId = [];
				$res = $this->getList(
					[
						'filter' => [
							'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
							[
								'LOGIC' => 'OR',
								'=CREATED_BY_ID' => 0,
								'@XML_ID' => $defPresetsXmlIds,
							]

						],
						'select' => ['ID', 'COUNTRY_ID', 'XML_ID']
					]
				);
				unset($defPresetsXmlIds);
				if (is_object($res))
				{
					$requisite = EntityRequisite::getSingleInstance();
					$emptyXmlIdIndex = 1;
					while ($row = $res->fetch())
					{
						$id = (int)$row['ID'];
						$presetCountryId = (int)$row['COUNTRY_ID'];
						$deleted = 'N';

						if ($presetCountryId !== $countryId)
						{
							$resRq = $requisite->getList(
								[
									'filter' => ['=PRESET_ID' => $id],
									'select' => ['ID'],
									'limit' => 1,
								]
							);
							if (is_object($resRq))
							{
								$rowRq = $resRq->fetch();
								if (!$rowRq)
								{
									// Remove directly through the table because you need to avoid
									// checks and setting default presets.
									$delResult = PresetTable::delete($id);
									if ($delResult->isSuccess())
									{
										$deleted = 'Y';
									}
								}
							}
						}

						$xmlId = '';
						if (isset($row['XML_ID']) && is_string($row['XML_ID']) && $row['XML_ID'] !== '')
						{
							$xmlId = $row['XML_ID'];
							$index = $row['XML_ID'];
						}
						else
						{
							$index = "<EMPTY_XML_ID_$emptyXmlIdIndex>";
							$emptyXmlIdIndex++;
						}
						$emptyXmlIdIndex++;
						$existsDefPresetMap[$id] = [
							'COUNTRY_ID' => $presetCountryId,
							'XML_ID' => $xmlId,
							'deleted' => $deleted
						];
						$existsDefPresetsByXmlId[$index] = [
							'ID' => $id,
							'COUNTRY_ID' => $presetCountryId,
							'deleted' => $deleted
						];
					}
				}
				unset(
					$id,
					$deleted,
					$row,
					$res,
					$rowRq,
					$resRq,
					$presetCountryId,
					$requisite,
					$resDel,
					$defPresetsOptionValue,
					$emptyXmlIdIndex,
					$xmlId,
					$index
				);
				//endregion Delete all default presets for which there are no requisites.

				//region Create new default presets.
				$sort = 500;
				$datetimeEntity = new Main\DB\SqlExpression(
					Main\Application::getConnection()->getSqlHelper()->getCurrentDateTimeFunction()
				);
				foreach ($fixedPresetList as $presetData)
				{
					if ($countryId === (int)$presetData['COUNTRY_ID'])
					{
						$sort += 10;
						if (
							!isset($existsDefPresetsByXmlId[$presetData['XML_ID']])
							|| (
								isset($existsDefPresetsByXmlId[$presetData['XML_ID']]['deleted'])
								&& $existsDefPresetsByXmlId[$presetData['XML_ID']]['deleted'] === 'Y'
							)
						)
						{
							$presetFields = [
								'ENTITY_TYPE_ID' => EntityPreset::Requisite,
								'COUNTRY_ID' => $countryId,
								'DATE_CREATE' => $datetimeEntity,
								'CREATED_BY_ID' => 0,
								'NAME' => $presetData['NAME'],
								'ACTIVE' => $presetData['ACTIVE'],
								'SORT' => $sort,
								'XML_ID' => $presetData['XML_ID'],
								'SETTINGS' => $presetData['SETTINGS']
							];

							//region Rename existing presets if their names match the new one.
							$res = $this->getList(
								[
									'filter' => [
										'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
										'=NAME' => $presetFields['NAME'],
									],
									'select' => ['ID', 'NAME', 'COUNTRY_ID'],
								]
							);
							if (is_object($res))
							{
								while ($row = $res->fetch())
								{
									$countryCode = EntityPreset::getCountryCodeById($row['COUNTRY_ID']);
									if ($countryCode !== '')
									{
										PresetTable::update(
											(int)$row['ID'],
											['NAME' => $row['NAME'] . " ($countryCode)"]
										);
									}
								}
							}
							unset($res, $row, $countryCode);
							//endregion Rename existing presets if their names match the new one.

							PresetTable::add($presetFields);
						}
					}
				}
				unset(
					$existsDefPresetsByXmlId,
					$fixedPresetList,
					$presetData,
					$sort,
					$presetFields
				);
				//endregion Create new default presets.

				// Set new identifier of the current country.
				Option::set('crm', 'crm_requisite_preset_country_id', $countryId);

				$entityTypeNames = ['COMPANY', 'CONTACT'];

				//region Get current default presets identifiers
				$defPresetMap = [];
				foreach ($entityTypeNames as $entityTypeName)
				{
					$defPresetMap[$entityTypeName] = [
						'ID' => 0,
						'COUNTRY_ID' => 0,
						'NAME' => '',
						'SETTINGS' => []
					];
				}
				unset($entityTypeName);
				$optionValue = Option::get('crm', 'requisite_default_presets');
				$optionModified = false;
				if ($optionValue !== '')
				{
					$optionValue = unserialize($optionValue, ['allowed_classes' => false]);
				}
				if (!is_array($optionValue))
				{
					$optionValue = [];
				}
				foreach ($entityTypeNames as $entityTypeName)
				{
					if (isset($optionValue[$entityTypeName]))
					{
						$defPresetMap[$entityTypeName]['ID'] = (int)$optionValue[$entityTypeName];
						if ($defPresetMap[$entityTypeName]['ID'] < 0)
						{
							$defPresetMap[$entityTypeName]['ID'] = 0;
							$optionModified = true;
						}
					}
				}
				unset($entityTypeName);

				//region Check existing of default presets
				$existsDefPreset = [];
				foreach ($entityTypeNames as $entityTypeName)
				{
					$existsDefPreset[$entityTypeName] = false;
				}
				unset($entityTypeName);
				if ($defPresetMap['COMPANY']['ID'] > 0 || $defPresetMap['CONTACT']['ID'] > 0)
				{
					$ids = [];
					foreach ($entityTypeNames as $entityTypeName)
					{
						if (
							$defPresetMap[$entityTypeName]['ID'] > 0
							&& !in_array($defPresetMap[$entityTypeName]['ID'], $ids, true)
						)
						{
							$ids[] = $defPresetMap[$entityTypeName]['ID'];
						}
					}
					unset($entityTypeName);

					if (!empty($ids))
					{
						$res = $this->getList(
							[
								'filter' => [
									'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
									'@ID' => $ids
								],
								'select' => ['ID', 'COUNTRY_ID', 'NAME', 'SETTINGS'],
							]
						);
						if (is_object($res))
						{
							while ($row = $res->fetch())
							{
								$presetId = is_array($row) && isset($row['ID']) ? (int)$row['ID'] : 0;
								if ($presetId > 0)
								{
									foreach ($entityTypeNames as $entityTypeName)
									{
										if ($presetId === $defPresetMap[$entityTypeName]['ID'])
										{
											$existsDefPreset[$entityTypeName] = true;
											$defPresetMap[$entityTypeName]['COUNTRY_ID'] = (int)$row['COUNTRY_ID'];
											if ($defPresetMap[$entityTypeName]['COUNTRY_ID'] < 0)
											{
												$defPresetMap[$entityTypeName]['COUNTRY_ID'] = 0;
											}
											if (isset($row['NAME']) && is_string($row['NAME']) && $row['NAME'] !== '')
											{
												$defPresetMap[$entityTypeName]['NAME'] = $row['NAME'];
											}
											if (is_array($row['SETTINGS']))
											{
												$defPresetMap[$entityTypeName]['SETTINGS'] = $row['SETTINGS'];
											}
										}
									}
								}
							}
						}
						unset($ids, $res, $row, $presetId, $entityTypeName);
					}
				}
				foreach ($entityTypeNames as $entityTypeName)
				{
					if (!$existsDefPreset[$entityTypeName])
					{
						$defPresetMap[$entityTypeName]['ID'] = 0;
						$optionModified = true;
					}
				}
				unset($entityTypeName, $existsDefPreset);
				//endregion Check existing of default presets

				$optionValue = [];
				foreach ($entityTypeNames as $entityTypeName)
				{
					$optionValue[$entityTypeName] = $defPresetMap[$entityTypeName]['ID'];
				}
				unset($entityTypeNames, $entityTypeName);
				//endregion Get current default presets identifiers

				//region Reset default presets to option
				$countryCode = static::getCountryCodeById($countryId);
				$personTypeMap = [
					'COMPANY' => $countryCode === 'RU' ? 'COMPANY' : 'LEGALENTITY',
					'CONTACT' => 'PERSON',
				];
				foreach ($personTypeMap as $optionParamName => $personType)
				{
					$xmlId = str_replace(
						['%COUNTRY%', '%PERSON%'],
						[$countryCode, $personType],
						'#CRM_REQUISITE_PRESET_DEF_%COUNTRY%_%PERSON%#'
					);
					$res = $this->getList(
						[
							'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
							'filter' => [
								'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
								'=XML_ID' => $xmlId
							],
							'select' => ['ID', 'COUNTRY_ID', 'NAME', 'SETTINGS'],
							'limit' => 1
						]
					);
					if (is_object($res))
					{
						$row = $res->fetch();
						if (is_array($row))
						{
							$presetId = isset($row['ID']) ? (int)$row['ID'] : 0;
							$presetCountryId =
								(isset($row['COUNTRY_ID']) && $row['COUNTRY_ID'] > 0)
								? (int)$row['COUNTRY_ID']
								: 0
							;
							$presetName = (isset($row['NAME']) && is_string($row['NAME'])) ? $row['NAME'] : '';
							$settings = is_array($row['SETTINGS']) ? $row['SETTINGS'] : [];
							if ($presetId > 0)
							{
								$optionValue[$optionParamName] = $presetId;
								$defPresetMap[$optionParamName]['ID'] = $presetId;
								$defPresetMap[$optionParamName]['COUNTRY_ID'] = $presetCountryId;
								$defPresetMap[$optionParamName]['NAME'] = $presetName;
								$defPresetMap[$optionParamName]['SETTINGS'] = $settings;
								$optionModified = true;
							}
						}
					}
				}
				unset(
					$personTypeMap,
					$optionParamName,
					$personType,
					$xmlId,
					$res,
					$row,
					$presetId,
					$presetCountryId,
					$presetName,
					$settings
				);

				if ($optionModified)
				{
					Option::set('crm', 'requisite_default_presets', serialize($optionValue));
				}
				unset($optionModified, $optionValue);
				//endregion Reset default presets to option

				//region Convert synchronization settings for order properties with requisite fields
				$this->convertOrderPropsSyncSettings(
					$defPresetMap,
					$existsDefPresetMap
				);
				//endregion Convert synchronization settings for order properties with requisite fields

				unset($defPresetMap, $existsDefPresetMap);
			}
		}

		return $result;
	}

	private function convertOrderPropsSyncSettings(array $newPresetMap, array $existsDefPresetMap)
	{
		//region Verification of initial data
		$presetMap = [];
		foreach (['COMPANY', 'CONTACT'] as $entityTypeName)
		{
			$isValidPreset = (
				is_array($newPresetMap[$entityTypeName])
				&& isset($newPresetMap[$entityTypeName]['ID'])
				&& $newPresetMap[$entityTypeName]['ID'] > 0
				&& isset($newPresetMap[$entityTypeName]['COUNTRY_ID'])
				&& $newPresetMap[$entityTypeName]['COUNTRY_ID'] > 0
				&& isset($newPresetMap[$entityTypeName]['NAME'])
				&& is_string($newPresetMap[$entityTypeName]['NAME'])
				&& is_array($newPresetMap[$entityTypeName]['SETTINGS'])
				&& is_array($newPresetMap[$entityTypeName]['SETTINGS']['FIELDS'])
			);
			$presetMap[$entityTypeName] = [
				'IS_VALID' => $isValidPreset,
				'ID' => $isValidPreset ? (int)$newPresetMap[$entityTypeName]['ID'] : 0,
				'COUNTRY_ID' => $isValidPreset ? (int)$newPresetMap[$entityTypeName]['COUNTRY_ID'] : 0,
				'NAME' => $isValidPreset ? $newPresetMap[$entityTypeName]['NAME'] : '',
				'SETTINGS' => $isValidPreset ? $newPresetMap[$entityTypeName]['SETTINGS'] : [],
			];
		}
		$newPresetMap = $presetMap;
		unset($entityTypeName, $isValidPreset, $presetMap);
		//endregion Verification of initial data

		if (
			($newPresetMap['COMPANY']['IS_VALID'] || $newPresetMap['CONTACT']['IS_VALID'])
			&& !empty($existsDefPresetMap)
		)
		{
			$bankDetail = EntityBankDetail::getSingleInstance();

			$res = OrderPropsMatchTable::getList(
				[
					'filter' => [
						'@CRM_ENTITY_TYPE' => [
							\CCrmOwnerType::Company,
							\CCrmOwnerType::Contact
						],
						'@CRM_FIELD_TYPE' => [
							BaseEntityMatcher::REQUISITE_FIELD_TYPE,
							BaseEntityMatcher::BANK_DETAIL_FIELD_TYPE
						]
					],
					'select' => [
						'ID',
						'CRM_ENTITY_TYPE',
						'CRM_FIELD_TYPE',
						'SETTINGS',
						'CRM_FIELD_CODE'
					]
				]
			);
			if (is_object($res))
			{
				$settingsFieldsMap = [];
				$bankDetailFieldsMap = $bankDetail->getRqFieldByCountry();
				while ($row = $res->fetch())
				{
					if (
						is_array($row['SETTINGS'])
						&& isset($row['SETTINGS']['RQ_PRESET_ID'])
						&& $row['SETTINGS']['RQ_PRESET_ID'] > 0
					)
					{
						$propPresetId = (int)$row['SETTINGS']['RQ_PRESET_ID'];
						if (is_array($existsDefPresetMap[$propPresetId]))
						{
							$entityTypeId = \CCrmOwnerType::Undefined;
							$presetInfo = $existsDefPresetMap[$propPresetId];

							//region Detect entity type
							if (
								isset($presetInfo['XML_ID'])
								&& is_string($presetInfo['XML_ID'])
								&& $presetInfo['XML_ID'] !== ''
							)
							{
								$xmlIdLength = mb_strlen($presetInfo['XML_ID']);
								$signMap = [
									'COMPANY' => \CCrmOwnerType::Company,
									'INDIVIDUAL' => \CCrmOwnerType::Company,
									'PERSON' => \CCrmOwnerType::Contact,
									'LEGALENTITY' => \CCrmOwnerType::Company,
								];
								foreach (array_keys($signMap) as $sign)
								{
									$suffix = mb_substr($presetInfo['XML_ID'], $xmlIdLength - mb_strlen($sign) - 1);
									if ($suffix === ($sign . '#'))
									{
										$entityTypeId = $signMap[$sign];
										break;
									}
								}
							}
							if ($entityTypeId === \CCrmOwnerType::Undefined)
							{
								if (isset($row['CRM_ENTITY_TYPE']) && $row['CRM_ENTITY_TYPE'] > 0)
								{
									$entityTypeId = (int)$row['CRM_ENTITY_TYPE'];
								}
								if (
									!(
										$entityTypeId === \CCrmOwnerType::Company
										|| $entityTypeId === \CCrmOwnerType::Contact
									)
								)
								{
									$entityTypeId = \CCrmOwnerType::Company;
								}
							}
							//endregion Detect entity type

							$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
							if (
								is_array($newPresetMap[$entityTypeName])
								&& $newPresetMap[$entityTypeName]['IS_VALID']
								&& isset($row['ID'])
								&& $row['ID'] > 0
								&& isset($row['CRM_FIELD_TYPE'])
								&& $row['CRM_FIELD_TYPE'] > 0
								&& isset($row['CRM_FIELD_CODE'])
								&& is_string($row['CRM_FIELD_CODE'])
								&& $row['CRM_FIELD_CODE'] !== ''
								&& isset($row['SETTINGS']['RQ_NAME'])
								&& isset($row['SETTINGS']['RQ_PRESET_ID'])
							)
							{
								$crmFieldType = (int)$row['CRM_FIELD_TYPE'];
								$presetId = $newPresetMap[$entityTypeName]['ID'];
								$presetCountryId = $newPresetMap[$entityTypeName]['COUNTRY_ID'];
								$fields = [
									'SETTINGS' => $row['SETTINGS']
								];
								$fields['SETTINGS']['RQ_PRESET_ID'] = $presetId;
								$fields['SETTINGS']['RQ_NAME'] = $newPresetMap[$entityTypeName]['NAME'];
								$needUpdate = false;

								if ($crmFieldType === BaseEntityMatcher::REQUISITE_FIELD_TYPE)
								{
									if (!isset($settingsFieldsMap[$presetId]))
									{
										$settingsFieldsMap[$presetId] = [];
										if (is_array($newPresetMap[$entityTypeName]['SETTINGS']['FIELDS']))
										{
											$presetFields = $newPresetMap[$entityTypeName]['SETTINGS']['FIELDS'];
											foreach ($presetFields as $presetField)
											{
												if (
													isset($presetField['FIELD_NAME'])
													&& is_string($presetField['FIELD_NAME'])
													&& $presetField['FIELD_NAME'] !== ''
												)
												{
													$settingsFieldsMap[$presetId][$presetField['FIELD_NAME']] = true;
												}
											}
										}
									}

									if (isset($settingsFieldsMap[$presetId][$row['CRM_FIELD_CODE']]))
									{
										$needUpdate = true;
									}
								}
								elseif ($crmFieldType === BaseEntityMatcher::BANK_DETAIL_FIELD_TYPE)
								{
									if (isset($bankDetailFieldsMap[$presetCountryId][$row['CRM_FIELD_CODE']]))
									{
										$fields['SETTINGS']['BD_NAME'] =
											$bankDetail->getDefaultSectionTitle($presetCountryId)
										;
										$fields['SETTINGS']['BD_COUNTRY_ID'] = $presetCountryId;
										$needUpdate = true;
									}
								}

								if ($needUpdate)
								{
									OrderPropsMatchTable::update((int)$row['ID'], ['fields' => $fields]);
								}
							}
						}
					}
				}
			}
		}
	}
}

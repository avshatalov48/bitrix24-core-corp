<?php

namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class EntityPreset
{
	const Undefined = 0;
	const Requisite = 8;    // refresh FirstEntityType and LastEntityType constants (see the CCrmOwnerType constants)
	const FirstEntityType = 8;
	const LastEntityType = 8;

	const NO_ERRORS = 0;
	const ERR_DELETE_PRESET_USED = 1;
	const ERR_PRESET_NOT_FOUND = 2;
	const ERR_INVALID_ENTITY_TYPE = 3;

	private static $singleInstance = null;

	private static $countryCodes = null;
	private static $countryInfo = null;
	private static $countryList = null;
	private static $fieldInfo = null;

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
		if (Main\Config\Option::get('crm', 'entity_preset_force_utf_mode', 'N') === 'Y')
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
		$entity = self::getSingleInstance();
		$dbResult = $entity->getList(
			array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'filter' => self::getActivePresetFilter(),
				'select' => array('ID', 'NAME')
			)
		);

		$results = array();
		while ($fields = $dbResult->fetch())
		{
			$results[$fields['ID']] = $fields['NAME'];
		}
		return $results;
	}

	public static function getListForRequisiteEntityEditor()
	{
		$entity = self::getSingleInstance();
		$dbResult = $entity->getList(
			array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'filter' => self::getActivePresetFilter(),
				'select' => array('ID', 'NAME', 'COUNTRY_ID')
			)
		);

		$results = array();
		while ($fields = $dbResult->fetch())
		{
			$results[$fields['ID']] = $fields;
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
				'cache' => 3600
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

	public function add($fields, $options = array())
	{
		unset($fields['ID'], $fields['DATE_MODIFY'], $fields['MODIFY_BY_ID']);
		$fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
		$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		return PresetTable::add($fields);
	}

	public function update($id, $fields, $options = array())
	{
		unset($fields['DATE_CREATE'], $fields['CREATED_BY_ID']);
		$fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$fields['MODIFY_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

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
			'cache' => 3600
		])->fetch();
		return $preset ? $preset['ID'] : false;
	}
}

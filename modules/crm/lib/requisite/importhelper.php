<?php
namespace Bitrix\Crm\Requisite;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Crm\EntityBankDetail;

Loc::loadMessages(__FILE__);

class ImportHelper
{
	const ERR_UNDEFINED = 1;
	const ERR_EMPTY_KEY_FIELDS = 2;
	const ERR_EMPTY_RQ_KEY_FIELDS = 3;
	const ERR_NEXT_ENTITY = 4;
	const ERR_ROW_LIMIT = 5;
	const ERR_RQ_KEY_FIELDS_NOT_PRESENT = 6;
	const ERR_RQ_NAME_IS_NOT_SET = 7;
	const ERR_PRESET_ASSOC = 8;
	const ERR_UNKNOWN_ADDRESS_TYPE = 9;
	const ERR_ADDRESS_TYPE_IS_NOT_SET = 10;
	const ERR_ADDRESS_TYPE_ALREADY_EXISTS = 11;
	const ERR_BD_KEY_FIELDS_NOT_PRESENT = 12;
	const ERR_EMPTY_BD_KEY_FIELDS = 13;
	const ERR_BD_NAME_IS_NOT_SET = 14;
	const ERR_INVALID_ENTITY_TYPE = 15;
	const ERR_INVALID_ENTITY_ID = 16;
	const ERR_COMPANY_NOT_EXISTS = 17;
	const ERR_CONTACT_NOT_EXISTS = 18;
	const ERR_ACCESS_DENIED_COMPANY_UPDATE = 19;
	const ERR_ACCESS_DENIED_CONTACT_UPDATE = 20;
	const ERR_DEF_IMP_PRESET_NOT_DEFINED = 21;
	const ERR_INVALID_IMP_PRESET_ID = 22;
	const ERR_IMP_PRESET_NOT_EXISTS = 23;
	const ERR_IMP_PRESET_HAS_NO_ADDR_FIELD = 24;
	const ERR_NO_ADDRESSES_TO_IMPORT = 25;
	const ERR_CREATE_REQUISITE = 26;
	const ERR_UPDATE_REQUISITE = 27;
	const ERR_CREATE_BANK_DETAIL = 28;
	const ERR_UPDATE_BANK_DETAIL = 29;

	private static $presetCacheById = array();
	private static $presetCacheByName = array();
	private static $addressTypeList = null;

	private $entityTypeId;
	private $headerIndex;
	private $headerGroupCountryIdMap;
	private $headerById;
	private $rows;
	private $rowNumber;
	private $entityKeyFields;
	private $entityKeyValue;
	private $searchNextEntityMode;
	private $ready;

	private $requisiteKeyFields;
	private $bankDetailKeyFields;

	private $rqFieldPrefix;
	private $addrFieldPrefix;
	private $bdFieldPrefix;

	private $requisiteList;

	// options
	private $rowLimit;
	private $assocPreset;
	private $assocPresetById;
	private $useDefPreset;
	private $defPresetId;

	/**
	 * Imports old addresses for the company or contact to requisites. Replaces deprecated method
	 * EntityRequisite::importEntityRequisite.
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID for import
	 * @param string $dupControlType Duplicate control type ("NO_CONTROL", "REPLACE", "MERGE", "SKIP")
	 * @param int $presetId Preset ID for import
	 * @param array $fields Fields of the requisite to import
	 * @return Main\Result
	 */
	public static function importOldRequisiteAddresses($entityTypeId, $entityId, $dupControlType, $presetId = 0,
		$fields)
	{
		$result = new Main\Result();

		if(!in_array($dupControlType, array('REPLACE', 'MERGE', 'SKIP'), true))
			$dupControlType = 'NO_CONTROL';
		$rqImportMode = 'MERGE';
		switch ($dupControlType)
		{
			case 'REPLACE':
				$rqImportMode = $dupControlType;
				break;
		}

		if (!EntityRequisite::checkEntityType($entityTypeId))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_RQ_IMP_HLPR_ERR_INVALID_ENTITY_TYPE'),
					self::ERR_INVALID_ENTITY_TYPE
				)
			);
			return $result;
		}

		if ($entityId <= 0)
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_RQ_IMP_HLPR_ERR_INVALID_ENTITY_ID'),
					self::ERR_INVALID_ENTITY_ID
				)
			);
			return $result;
		}

		$requisite = EntityRequisite::getSingleInstance();
		if (!$requisite->validateEntityExists($entityTypeId, $entityId))
		{
			$errMsg = '';
			$errCode = 0;
			switch ($entityTypeId)
			{
				case \CCrmOwnerType::Company:
					$errMsg = GetMessage('CRM_RQ_IMP_HLPR_ERR_COMPANY_NOT_EXISTS', array('#ID#' => $entityId));
					$errCode = self::ERR_COMPANY_NOT_EXISTS;
					break;
				case \CCrmOwnerType::Contact:
					$errMsg = GetMessage('CRM_RQ_IMP_HLPR_ERR_CONTACT_NOT_EXISTS', array('#ID#' => $entityId));
					$errCode = self::ERR_CONTACT_NOT_EXISTS;
					break;
			}
			$result->addError(new Main\Error($errMsg, $errCode));
			return $result;
		}

		if (!EntityRequisite::checkUpdatePermissionOwnerEntity($entityTypeId, $entityId))
		{
			$errMsg = '';
			$errCode = 0;
			switch ($entityTypeId)
			{
				case \CCrmOwnerType::Company:
					$errMsg = GetMessage(
						'CRM_RQ_IMP_HLPR_ERR_ACCESS_DENIED_COMPANY_UPDATE',
						array('#ID#' => $entityId)
					);
					$errCode = self::ERR_ACCESS_DENIED_COMPANY_UPDATE;
					break;
				case \CCrmOwnerType::Contact:
					$errMsg = GetMessage(
						'CRM_RQ_IMP_HLPR_ERR_ACCESS_DENIED_CONTACT_UPDATE',
						array('#ID#' => $entityId)
					);
					$errCode = self::ERR_ACCESS_DENIED_CONTACT_UPDATE;
					break;
			}
			$result->addError(new Main\Error($errMsg, $errCode));
			return $result;
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
		if ($presetId === 0)
		{
			$presetId = EntityRequisite::getDefaultPresetId($entityTypeId);

			if ($presetId <= 0)
			{
				$result->addError(
					new Main\Error(
						GetMessage(
							'CRM_RQ_IMP_HLPR_ERR_DEF_IMP_PRESET_NOT_DEFINED',
							array('#ENTITY_TYPE_NAME_GENITIVE#' => $entityTypeName)
						),
						self::ERR_DEF_IMP_PRESET_NOT_DEFINED
					)
				);
				return $result;
			}
		}

		$presetId = (int)$presetId;
		if ($presetId <= 0)
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_RQ_IMP_HLPR_ERR_INVALID_IMP_PRESET_ID'),
					self::ERR_INVALID_IMP_PRESET_ID
				)
			);
			return $result;
		}

		$preset = EntityPreset::getSingleInstance();
		$presetInfo = $preset->getById($presetId);
		$fieldsInPreset = array();
		if (!is_array($presetInfo))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_RQ_IMP_HLPR_ERR_IMP_PRESET_NOT_EXISTS', array('#ID#' => $presetId)),
					self::ERR_IMP_PRESET_NOT_EXISTS
				)
			);
			return $result;
		}
		$presetName = EntityPreset::formatName($presetId, $presetInfo['NAME']);
		if (is_array($presetInfo['SETTINGS']))
		{
			$presetFieldsInfo = $preset->settingsGetFields($presetInfo['SETTINGS']);
			foreach ($presetFieldsInfo as $fieldInfo)
			{
				if (isset($fieldInfo['FIELD_NAME']) && !empty($fieldInfo['FIELD_NAME']))
					$fieldsInPreset[$fieldInfo['FIELD_NAME']] = true;
			}
		}
		if (!isset($fieldsInPreset[EntityRequisite::ADDRESS]))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_RQ_IMP_HLPR_ERR_IMP_PRESET_HAS_NO_ADDR_FIELD', array('#ID#' => $presetId)),
					self::ERR_IMP_PRESET_HAS_NO_ADDR_FIELD
				)
			);
			return $result;
		}
		unset($preset, $presetInfo, $presetHasAddress, $presetFieldsInfo, $fieldInfo);

		$addresses = array();
		$rqAddrTypeInfos = RequisiteAddress::getTypeInfos();
		$addressFields = array(
			'ADDRESS_1',
			'ADDRESS_2',
			'CITY',
			'POSTAL_CODE',
			'REGION',
			'PROVINCE',
			'COUNTRY',
			'COUNTRY_CODE'
		);
		$rqAddrTypes = array_keys($rqAddrTypeInfos);
		if (is_array($fields)
			&& is_array($fields[EntityRequisite::ADDRESS])
			&& !empty($fields[EntityRequisite::ADDRESS]))
		{
			foreach ($fields[EntityRequisite::ADDRESS] as $addrTypeId => $address)
			{
				if (in_array($addrTypeId, $rqAddrTypes, true) && !RequisiteAddress::isEmpty($address))
				{
					foreach ($addressFields as $fieldName)
					{
						$addresses[$addrTypeId][$fieldName] =
							isset($address[$fieldName]) ? $address[$fieldName] : null;
					}
				}
			}
		}
		if (empty($addresses))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_RQ_IMP_HLPR_ERR_NO_ADDRESSES_TO_IMPORT', array('#ID#' => $presetId)),
					self::ERR_NO_ADDRESSES_TO_IMPORT
				)
			);
			return $result;
		}

		$rqIsFound = false;
		$rqListResult = $requisite->getList(
			array(
				'select' => array('ID'),
				'filter' => array(
					'=PRESET_ID' => $presetId,
					'=ENTITY_TYPE_ID' => $entityTypeId,
					'=ENTITY_ID' => $entityId
				)
			)
		);
		while($rqRow = $rqListResult->fetch())
		{
			$requisiteId = (int)$rqRow['ID'];
			$rqIsFound = true;
			$requisiteAddresses = EntityRequisite::getAddresses($requisiteId);
			foreach($addresses as $addrTypeId => $address)
			{
				// $rqImportMode may be only 'REPLACE' or 'MERGE'
				if(!isset($requisiteAddresses[$addrTypeId])
					|| RequisiteAddress::isEmpty($requisiteAddresses[$addrTypeId])
					|| ($rqImportMode === 'REPLACE'
						&&  !RequisiteAddress::areEquals($addresses[$addrTypeId], $requisiteAddresses[$addrTypeId])))
				{
					RequisiteAddress::register(\CCrmOwnerType::Requisite, $requisiteId, $addrTypeId, $address);
				}
			}
		}
		if (!$rqIsFound)
		{
			$requisiteFields = array(
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'PRESET_ID' => $presetId,
				'NAME' => $presetName,
				'SORT' => 500,
				'ACTIVE' => 'Y'
			);
			foreach (array_keys($fieldsInPreset) as $fieldName)
			{
				if (isset($fields[$fieldName]))
					$requisiteFields[$fieldName] = $fields[$fieldName];
			}
			$requisiteAddResult = $requisite->add($requisiteFields);
			if(!$requisiteAddResult->isSuccess())
			{
				$rqAddErrors = $requisiteAddResult->getErrorMessages();
				$rqAddErrorStr = GetMessage(
					'CRM_RQ_IMP_HLPR_ERR_CREATE_REQUISITE',
					array(
						'#ENTITY_TYPE_NAME_GENITIVE#' => GetMessage(
							'CRM_RQ_IMP_HLPR_ERR_'.$entityTypeName.'_GENITIVE'
						),
						'#ID#' => $entityId,
					)
				);
				if (is_array($rqAddErrors) && !empty($rqAddErrors))
					$rqAddErrorStr .= ': '.$rqAddErrors[0];
				$result->addError(
					new Main\Error(
						$rqAddErrorStr,
						self::ERR_CREATE_REQUISITE
					)
				);
				return $result;
			}
		}

		return $result;
	}

	/**
	 * Preparing the headers of requisites to be imported and list of active countries.
	 * @param int $entityTypeId Entity type ID.
	 * @param array $options Options, such as prefix and others.
	 *
	 * @return array Array with headers for import the requisites
	 */
	public static function prepareEntityImportRequisiteInfo($entityTypeId, $options = array())
	{
		$activeCountryList = array();
		$requisiteHeaders = array();

		$rqPrefix = (is_array($options) && isset($options['PREFIX']) && is_string($options['PREFIX']))
			? $options['PREFIX']
			: (is_array($options) && isset($options['RQ_PREFIX']) && is_string($options['RQ_PREFIX'])
				? $options['RQ_PREFIX'] : 'RQ_');
		$bdPrefix = (is_array($options) && isset($options['BD_PREFIX']) && is_string($options['BD_PREFIX'])) ?
			$options['BD_PREFIX'] : 'BD_';

		$presetIds = array();
		if (is_array($options) && is_array($options['PRESET_IDS']) && !empty($options['PRESET_IDS']))
			$presetIds = $options['PRESET_IDS'];

		if (EntityRequisite::checkEntityType($entityTypeId))
		{
			$preset = EntityPreset::getSingleInstance();
			$opts = array('ARRANGE_BY_COUNTRY' => true);
			if (!empty($presetIds))
				$opts['FILTER_BY_PRESET_IDS'] = $presetIds;
			else
				$opts['FILTER_BY_COUNTRY_IDS'] = EntityRequisite::getAllowedRqFieldCountries();
			$fieldList = $preset->getSettingsFieldsOfPresets(\Bitrix\Crm\EntityPreset::Requisite, 'active', $opts);
			unset($opts);
			$activeCountries = array();
			$activeFieldsByCountry = array();
			foreach ($fieldList as $countryId => $fields)
			{
				foreach ($fields as $fieldName)
				{
					if (!isset($activeFieldsByCountry[$countryId][$fieldName]))
						$activeFieldsByCountry[$countryId][$fieldName] = true;
					if (!isset($activeCountries[$countryId]))
						$activeCountries[$countryId] = true;
				}
			}
			if (!empty($activeCountries))
			{
				// fill requisite headers
				$currentCountryId = EntityPreset::getCurrentCountryId();
				$countrySort = array();
				if (isset($activeCountries[$currentCountryId]))
				{
					$countrySort[] = $currentCountryId;
				}
				foreach (array_keys($activeCountries) as $countryId)
				{
					if ($countryId !== $currentCountryId)
						$countrySort[] = $countryId;
				}

				// requisite field types
				$requisite = EntityRequisite::getSingleInstance();
				$rqFieldTypeInfoMap = array();
				foreach ($requisite->getFormFieldsInfo() as $fieldName => $fieldInfo)
				{
					$rqFieldTypeInfoMap[$fieldName] = array(
						'type' => $fieldInfo['type'],
						'isUF' => $fieldInfo['isUF']
					);
				}

				// address field types
				$addrFieldTypeMap = array('TYPE' => 'integer');
				foreach (RequisiteAddress::getFieldsInfo() as $fieldName => $fieldInfo)
					$addrFieldTypeMap[$fieldName] = $fieldInfo['TYPE'];

				$rqFieldTitleMap = $requisite->getRqFieldTitleMap();
				$userFieldTitles = $requisite->getUserFieldsTitles();
				$countryList = EntityPreset::getCountryList();
				foreach (EntityRequisite::getBasicExportFieldsInfo() as $fieldName => $fieldInfo)
				{
					$fieldTitle = (is_array($fieldInfo) && isset($fieldInfo['title'])) ? $fieldInfo['title'] : '';
					if (!is_string($fieldTitle) || strlen($fieldTitle) <= 0)
						$fieldTitle = $fieldName;
					$fieldType = (is_array($fieldInfo) && isset($fieldInfo['type'])) ? $fieldInfo['type'] : 'string';
					$requisiteHeaders[] = array(
						'id' => $rqPrefix.$fieldName,
						'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').': '.$fieldTitle,
						'group' => 'requisite',
						'field' => $fieldName,
						'fieldType' => $fieldType,
						'isUF' => false,
						'countryId' => 0
					);
				}
				$addressLabels = null;
				$addressFields = null;
				foreach ($countrySort as $countryId)
				{
					if (isset($countryList[$countryId]))
					{
						$activeCountryList[$countryId] = $countryList[$countryId];

						foreach (array_keys($activeFieldsByCountry[$countryId]) as $fieldName)
						{
							if (isset($userFieldTitles[$fieldName]))
							{
								$fieldTitle = $userFieldTitles[$fieldName];
							}
							else
							{
								$fieldTitle = isset($rqFieldTitleMap[$fieldName][$countryId]) ?
									$rqFieldTitleMap[$fieldName][$countryId] : '';
							}

							if (!is_string($fieldTitle) || strlen($fieldTitle) <= 0)
								$fieldTitle = $fieldName;

							$fieldType = 'string';
							$isUF = false;
							if (is_array($rqFieldTypeInfoMap[$fieldName]))
							{
								if (isset($rqFieldTypeInfoMap[$fieldName]['type']))
									$fieldType = $rqFieldTypeInfoMap[$fieldName]['type'];
								if (isset($rqFieldTypeInfoMap[$fieldName]['isUF']))
									$isUF = $rqFieldTypeInfoMap[$fieldName]['isUF'];
							}
							$requisiteHeaders[] = array(
								'id' => $rqPrefix."$fieldName|$countryId",
								'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').
									' ('.$countryList[$countryId].')'.': '.$fieldTitle,
								'group' => 'requisite',
								'field' => $fieldName,
								'fieldType' => $fieldType,
								'isUF' => $isUF,
								'countryId' => $countryId
							);

							// headers for separated address fields
							if ($fieldName === EntityRequisite::ADDRESS)
							{
								$addressTypeLabel = GetMessage('CRM_REQUISITE_EXPORT_ADDRESS_TYPE_LABEL');
								if (!is_string($addressTypeLabel) || strlen($addressTypeLabel) <= 0)
									$addressTypeLabel = $fieldName.'_TYPE';
								if ($addressLabels === null)
								{
									$addressLabels = array_merge(
										array('TYPE' => $addressTypeLabel),
										RequisiteAddress::getShortLabels(RequisiteAddress::Primary)
									);
								}
								if ($addressFields === null)
								{
									$addressFields = array_merge(
										array('TYPE'),
										array_keys($requisite->getAddressFieldMap(RequisiteAddress::Primary))
									);
								}
								foreach ($addressFields as $addrFieldName)
								{
									if ($addrFieldName === 'COUNTRY_CODE')
										continue;

									$requisiteHeaders[] = array(
										'id' => $rqPrefix."{$fieldName}_{$addrFieldName}|$countryId",
										'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').
											' ('.$countryList[$countryId].')'.': '.$fieldTitle.' - '.
											ToLower($addressLabels[$addrFieldName]),
										'group' => 'address',
										'field' => $addrFieldName,
										'fieldType' => isset($addrFieldTypeMap[$addrFieldName]) ?
											$addrFieldTypeMap[$addrFieldName] : 'string',
										'isUF' => false,
										'countryId' => $countryId
									);
								}
							}
						}
					}
				}

				$bankDetail = EntityBankDetail::getSingleInstance();

				// bank detail field types
				$bdFieldTypeMap = array();
				foreach ($bankDetail->getFormFieldsInfo() as $fieldName => $fieldInfo)
					$bdFieldTypeMap[$fieldName] = $fieldInfo['type'];

				// fill bank detail headers
				foreach (EntityBankDetail::getBasicExportFieldsInfo() as $fieldName => $fieldInfo)
				{
					$fieldTitle = (is_array($fieldInfo) && isset($fieldInfo['title'])) ? $fieldInfo['title'] : '';
					if (!is_string($fieldTitle) || strlen($fieldTitle) <= 0)
						$fieldTitle = $fieldName;
					$fieldType = (is_array($fieldInfo) && isset($fieldInfo['type'])) ? $fieldInfo['type'] : 'string';
					$requisiteHeaders[] = array(
						'id' => $bdPrefix."$fieldName",
						'name' => GetMessage('CRM_BANK_DETAIL_FILTER_PREFIX').': '.$fieldTitle,
						'group' => 'bankDetail',
						'field' => $fieldName,
						'fieldType' => $fieldType,
						'isUF' => false,
						'countryId' => 0
					);
				}
				$bankDetailRqFieldCountryMap = $bankDetail->getRqFieldByCountry();
				$bankDetailRqFieldTitleMap = $bankDetail->getRqFieldTitleMap();
				foreach ($countrySort as $countryId)
				{
					if (!isset($bankDetailRqFieldCountryMap[$countryId]))
						continue;

					foreach ($bankDetailRqFieldCountryMap[$countryId] as $fieldName)
					{
						$fieldTitle = isset($bankDetailRqFieldTitleMap[$fieldName][$countryId]) ?
							$bankDetailRqFieldTitleMap[$fieldName][$countryId] : '';
						if (!is_string($fieldTitle) || strlen($fieldTitle) <= 0)
							$fieldTitle = $fieldName;
						$requisiteHeaders[] = array(
							'id' => $bdPrefix."$fieldName|$countryId",
							'name' => GetMessage('CRM_BANK_DETAIL_FILTER_PREFIX').
								' ('.$countryList[$countryId].')'.': '.$fieldTitle,
							'group' => 'bankDetail',
							'field' => $fieldName,
							'fieldType' => isset($bdFieldTypeMap[$fieldName]) ? $bdFieldTypeMap[$fieldName] : 'string',
							'isUF' => false,
							'countryId' => $countryId
						);
					}
				}
			}
		}

		$result = array(
			'REQUISITE_HEADERS' => $requisiteHeaders,
			'ACTIVE_COUNTRIES' => $activeCountryList
		);

		return $result;
	}

	public static function getRequisiteDupControlImportOptions($headers, $activeCountryList,
		$optionPrefix = 'IMPORT_DUP_CONTROL_ENABLE_RQ')
	{
		$result = array();

		$optionPrefix = is_string($optionPrefix) ? $optionPrefix : '';

		$dupControlFieldMap = array();
		foreach (EntityRequisite::getDuplicateCriterionFieldsMap() as $countryId => $fields)
			$dupControlFieldMap['requisite'][$countryId] = array_fill_keys($fields, true);
		foreach (EntityBankDetail::getDuplicateCriterionFieldsMap() as $countryId => $fields)
			$dupControlFieldMap['bankDetail'][$countryId] = array_fill_keys($fields, true);
		$dupHeaders = array();

		foreach($headers as $header)
		{
			if (isset($header['group']) && isset($header['field']) && isset($header['countryId']))
			{
				if (isset($dupControlFieldMap[$header['group']][$header['countryId']][$header['field']]))
				{
					$dupHeaders[$header['countryId']][$header['group']][$header['field']] = $header;
				}
			}
		}

		$fieldTitleMap = array();
		$requisite = EntityRequisite::getSingleInstance();
		$fieldTitleMap['requisite'] = $requisite->getRqFieldTitleMap();
		$bankDetail = EntityBankDetail::getSingleInstance();
		$fieldTitleMap['bankDetail'] = $bankDetail->getRqFieldTitleMap();

		foreach ($activeCountryList as $countryId => $countryName)
		{
			if (isset($dupHeaders[$countryId]))
			{
				foreach ($dupHeaders[$countryId] as $groupName => $headers)
				{
					switch ($groupName)
					{
						case 'requisite':
							$groupId = 'RQ';
							break;
						case 'bankDetail':
							$groupId = 'BD';
							break;
						default:
							$groupId = '';
					}
					if ($groupId !== '')
					{
						foreach ($headers as $fieldName => $header)
						{
							$optionId = $optionPrefix.'['.$countryId.']['.$groupId.']['.$fieldName.']';
							$optionName = isset($fieldTitleMap[$groupName][$fieldName][$countryId]) ?
								$fieldTitleMap[$groupName][$fieldName][$countryId] : $fieldName;
							$result[] = array(
								'id' => $optionId,
								'name' => $optionName,
								'groupId' => $groupId,
								'group' => $groupName,
								'field' => $fieldName,
								'countryId' => $countryId,
								'countryName' => $activeCountryList[$countryId]
							);
						}
					}
				}
			}
		}

		return $result;
	}

	public static function getRequisiteDemoData($entityTypeId, $exportHeaders, $presetId)
	{
		$result = array();

		if ($entityTypeId !== \CCrmOwnerType::Contact && $entityTypeId !== \CCrmOwnerType::Company)
			return $result;

		$presetId = (int)$presetId;
		if ($presetId <= 0)
			return $result;

		$presetName = '';
		$countryId = 0;

		$preset = EntityPreset::getSingleInstance();
		$res = $preset->getList(array(
			'filter' => array(
				'=ENTITY_TYPE_ID' => EntityPreset::Requisite,
				'=ID' => $presetId,
				'=ACTIVE' => 'Y'
			),
			'select' => array('ID', 'NAME', 'COUNTRY_ID'),
			'limit' => 1
		));
		if ($row = $res->fetch())
		{
			$presetName = isset($row['NAME']) ? $row['NAME'] : '';
			$countryId = isset($row['COUNTRY_ID']) ? (int)$row['COUNTRY_ID'] : 0;
		}
		unset($res, $row);

		if (!is_string($presetName) || strlen($presetName) <= 0 || $countryId <= 0)
			return $result;

		$allowedCountries = EntityRequisite::getAllowedRqFieldCountries();
		$countryList = EntityPreset::getCountryList();
		if (!isset($countryList[$countryId]) || !in_array($countryId, $allowedCountries, true))
			return $result;
		$countryName = $countryList[$countryId];
		unset($allowedCountries, $countryList);

		$demoRequisiteData = DemoData::getRequisiteImpotDemoData(array($entityTypeId), array($countryId));
		if (!is_array($demoRequisiteData[$countryId]) || !is_array($demoRequisiteData[$countryId][$entityTypeId]))
			return $result;

		$demoRequisiteData = $demoRequisiteData[$countryId][$entityTypeId];
		$requisiteId = $bankDetailId = 0;
		$rqSortNum = 500;
		$rqSortStep = 10;
		foreach ($demoRequisiteData as &$requisiteFields)
		{
			$requisiteFields['ID'] = ++$requisiteId;
			$requisiteFields['PRESET_ID'] = $presetId;
			$requisiteFields['PRESET_NAME'] = $presetName;
			$requisiteFields['PRESET_COUNTRY_ID'] = $countryId;
			$requisiteFields['PRESET_COUNTRY_NAME'] = $countryName;
			$requisiteFields['ACTIVE'] = Loc::getMessage('MAIN_YES');
			$requisiteFields['SORT'] = $rqSortNum;
			$rqSortNum += $rqSortStep;

			if (is_array($requisiteFields['BANK_DETAILS']))
			{
				$bdSortNum = 500;
				$bdSortStep = 10;
				foreach ($requisiteFields['BANK_DETAILS'] as &$bankDetailFields)
				{
					$bankDetailFields['ID'] = ++$bankDetailId;
					$bankDetailFields['ACTIVE'] = Loc::getMessage('MAIN_YES');
					$bankDetailFields['SORT'] = $bdSortNum;
					$bdSortNum += $bdSortStep;
				}
				unset($bankDetailFields);
			}
		}
		unset($requisiteFields);

		$demoRequisiteData = array($demoRequisiteData);

		$requisite = EntityRequisite::getSingleInstance();
		$demoRequisiteData = $requisite->entityListRequisiteExportDataFormatMultiline($demoRequisiteData, $exportHeaders);

		if (is_array($demoRequisiteData[0]))
			$result = $demoRequisiteData[0];

		return $result;
	}

	private function parseEntityKey($row)
	{
		$keyValue = '';

		if (is_array($row))
		{
			foreach ($this->entityKeyFields as $fieldName)
			{
				if (isset($this->headerIndex[$fieldName]))
				{
					$index = $this->headerIndex[$fieldName];
					if (isset($row[$index]))
						$keyValue .= strval($row[$index]);
				}
			}
		}

		return $keyValue;
	}

	public function getCurrentEntityKey()
	{
		return $this->entityKeyValue;
	}

	public function enableSearchNextEntityMode($prevEntityKeyValue)
	{
		$this->entityKeyValue = $prevEntityKeyValue;
		$this->searchNextEntityMode = true;
	}

	public function getErrorCode(Main\Result $result)
	{
		$errorCode = self::ERR_UNDEFINED;

		$errors = $result->getErrors();
		if (isset($errors[0]))
		{
			$errorCode = $errors[0]->getCode();
		}

		return $errorCode;
	}

	public function getErrorMessage(Main\Result $result)
	{
		$errorMessage = '';

		$errors = $result->getErrors();
		if (isset($errors[0]))
		{
			$errorMessage = $errors[0]->getMessage();
		}

		return $errorMessage;
	}

	public function __construct($entityTypeId, $headerIndex, $headerInfo, $options)
	{
		if ($entityTypeId !== \CCrmOwnerType::Company && $entityTypeId !== \CCrmOwnerType::Contact)
			throw new Main\ArgumentException('Incorrect entity type.', 'entityTypeId');

		if (!is_array($headerIndex) || empty($headerIndex))
			$headerIndex = [];

		if (!is_array($headerInfo) || empty($headerInfo))
			$headerInfo = [];

		$this->entityTypeId = $entityTypeId;

		switch ($this->entityTypeId)
		{
			case \CCrmOwnerType::Company:
				$this->entityKeyFields = array('ID', 'TITLE');
				break;
			case \CCrmOwnerType::Contact:
				$this->entityKeyFields = array('ID', 'NAME', 'LAST_NAME');
				break;
		}

		$this->requisiteKeyFields = array('ID', 'NAME');
		$this->bankDetailKeyFields = array('ID', 'NAME');

		$this->rqFieldPrefix = 'RQ_';
		$this->addrFieldPrefix = EntityRequisite::ADDRESS.'_';
		$this->bdFieldPrefix = 'BD_';

		$this->entityKeyValue = '';
		$this->searchNextEntityMode = false;
		$this->ready = false;

		$this->headerIndex = $headerIndex;

		$this->headerGroupCountryIdMap = array();
		$this->headerById = array();
		foreach ($headerInfo as $header)
		{
			if (is_array($header) && isset($header['id']) && is_string($header['id']) && strlen($header['id']) > 0
				&& isset($header['group']) && is_string($header['group']) && strlen($header['group']) > 0
				&& isset($header['countryId']) && $header['countryId'] >= 0 && isset($headerIndex[$header['id']]))
			{
				$this->headerById[$header['id']] = $header;
				$countryId = (int)$header['countryId'];
				$this->headerGroupCountryIdMap[$header['group']][$countryId][$header['id']] = true;
			}
		}

		$this->rows = array();
		$this->rowNumber = 0;

		$this->requisiteList = array();

		// region Options
		$rowLimit = (is_array($options) && isset($options['ROW_LIMIT'])) ? (int)$options['ROW_LIMIT'] : 50;
		if ($rowLimit <= 0)
			throw new Main\ArgumentException('Invalid number limit option for rows.', 'ROW_LIMIT');
		$this->rowLimit = $rowLimit;
		$this->assocPreset = (is_array($options) && isset($options['ASSOC_PRESET']) && $options['ASSOC_PRESET']);
		$this->assocPresetById = (is_array($options) && isset($options['ASSOC_PRESET_BY_ID'])
			&& $options['ASSOC_PRESET_BY_ID']);
		$this->useDefPreset = (is_array($options) && isset($options['USE_DEF_PRESET']) && $options['USE_DEF_PRESET']);
		$defPresetId = (is_array($options) && isset($options['DEF_PRESET_ID'])) ? (int)$options['DEF_PRESET_ID'] : 0;
		$this->defPresetId = $defPresetId > 0 ? $defPresetId : 0;
		// endregion Options
	}

	public function getRowCount()
	{
		return count($this->rows);
	}

	public function getRows()
	{
		return $this->rows;
	}

	public function getFirstRow()
	{
		$row = array();

		if (isset($this->rows[0]))
			$row = $this->rows[0];

		return $row;
	}

	public function parseRow($row)
	{
		$result = new Main\Result();

		if (($this->rowNumber + 1) > $this->rowLimit)
		{
			$result->addError(
				new Main\Error(
					Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_NEXT_ROW_LIMIT', array('#ROW_LIMIT#' => $this->rowLimit)),
					self::ERR_ROW_LIMIT
				)
			);

			return $result;
		}

		$this->rows[] = $row;
		$this->rowNumber++;

		$entityKeyValue = $this->parseEntityKey($row);
		if (!$this->searchNextEntityMode && $this->rowNumber === 1 && strlen($entityKeyValue) <= 0)
		{
			$result->addError(
				new Main\Error(Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_EMPTY_KEY_FIELDS'), self::ERR_EMPTY_KEY_FIELDS)
			);

			return $result;
		}

		if (strlen($entityKeyValue) > 0 && $this->entityKeyValue !== $entityKeyValue)
		{
			if ($this->rowNumber === 1)
			{
				$this->entityKeyValue = $entityKeyValue;
			}
			else
			{
				$result->addError(
					new Main\Error(Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_NEXT_ENTITY'), self::ERR_NEXT_ENTITY)
				);
			}

			if ($this->searchNextEntityMode)
			{
				$this->rows = array($this->rows[count($this->rows) - 1]);
				$this->rowNumber = 1;
				$this->searchNextEntityMode = false;
			}
			else if ($this->rowNumber > 1)
			{
				unset($this->rows[--$this->rowNumber]);
			}

			return $result;
		}

		return $result;
	}

	public function setReady($ready)
	{
		$this->ready = (!$this->searchNextEntityMode && $ready);
	}

	public function isReady()
	{
		return $this->ready;
	}

	public function parseRequisiteData()
	{
		$result = new Main\Result();

		$context = array(
			'requisiteIndex' => -1,
			'requisiteKey' => '',
			'presetInfo' => null,
			'bankDetailIndex' => -1,
			'rowNumber' => 0
		);

		$rowNumber = 0;
		foreach ($this->rows as $row)
		{
			$context['rowNumber'] = ++$rowNumber;
			$res = $this->parseRequisiteDataRow($context, $row);
			if (!$res->isSuccess())
			{
				$errors = $res->getErrorCollection();
				$error = $this->makeErrorWithRowNumber($errors[0], $rowNumber);
				$result->addError($error);

				return $result;
			}
		}

		return $result;
	}

	public function getParsedRequisites()
	{
		return $this->requisiteList;
	}

	public function getParsedRequisiteDupParams($requisiteDupControlFieldMap)
	{
		$result = array(
			'DUP_PARAM_LIST' => array(),
			'DUP_PARAM_FIELDS' => array()
		);

		if (!$this->isReady())
			return $result;

		$dupParamList = $dupParamFieldMap = array();
		$rqIndex = 0;
		foreach ($this->getParsedRequisites() as $requisiteFields)
		{
			$presetId = isset($requisiteFields['PRESET_ID']) ? (int)$requisiteFields['PRESET_ID'] : 0;
			$presetInfo = self::getCachedPresetInfo($presetId);
			if (is_array($presetInfo) && isset($presetInfo['COUNTRY_ID']))
			{
				$countryId = (int)$presetInfo['COUNTRY_ID'];
				$rqDupParams = null;
				if (is_array($requisiteDupControlFieldMap['requisite'][$countryId])
					&& !empty($requisiteDupControlFieldMap['requisite'][$countryId]))
				{
					foreach (array_keys($requisiteDupControlFieldMap['requisite'][$countryId]) as $fieldName)
					{
						if (isset($requisiteFields[$fieldName])
							&& ((is_string($requisiteFields[$fieldName])
									&& strlen($requisiteFields[$fieldName]) > 0)
								|| !empty($requisiteFields[$fieldName])))
						{
							if ($rqDupParams === null)
							{
								$rqDupParams = array(
									'ID' => 'n'.$rqIndex++,
									'PRESET_ID' => $presetId,
									'PRESET_COUNTRY_ID' => $countryId
								);
							}
							$rqDupParams[$fieldName] = $requisiteFields[$fieldName];
							$dupParamFieldMap['RQ.'.$fieldName.'|'.$countryId] = true;
						}
					}
				}
				if (is_array($requisiteFields['BANK_DETAILS']))
				{
					$bdIndex = 0;
					foreach ($requisiteFields['BANK_DETAILS'] as $bankDetailFields)
					{
						$bdDupParams = null;
						if (is_array($requisiteDupControlFieldMap['bankDetail'][$countryId])
							&& !empty($requisiteDupControlFieldMap['bankDetail'][$countryId]))
						{
							foreach (array_keys($requisiteDupControlFieldMap['bankDetail'][$countryId]) as $fieldName)
							{
								if (isset($bankDetailFields[$fieldName])
									&& ((is_string($bankDetailFields[$fieldName])
											&& strlen($bankDetailFields[$fieldName]) > 0)
										|| !empty($bankDetailFields[$fieldName])))
								{
									if ($rqDupParams === null)
									{
										$rqDupParams = array(
											'ID' => 'n'.$rqIndex++,
											'PRESET_ID' => $presetId,
											'PRESET_COUNTRY_ID' => $countryId
										);
									}
									if ($bdDupParams === null)
									{
										$bdDupParams = array(
											'ID' => 'n'.$bdIndex++,
											'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
											'ENTITY_ID' => $rqDupParams['ID'],
											'COUNTRY_ID' => $countryId
										);
									}
									$bdDupParams[$fieldName] = $bankDetailFields[$fieldName];
									$dupParamFieldMap['RQ.BD.'.$fieldName.'|'.$countryId] = true;
								}
							}
						}
						if (is_array($bdDupParams))
						{
							if (!is_array($rqDupParams['BD']))
								$rqDupParams['BD'] = array();
							$rqDupParams['BD'][$bdDupParams['ID']] = $bdDupParams;
						}
					}
				}
				if (is_array($rqDupParams))
				{
					$dupParamList[$rqDupParams['ID']] = $rqDupParams;
				}
			}
		}

		if (!empty($dupParamList))
			$result['DUP_PARAM_LIST'] = $dupParamList;
		if (!empty($dupParamFieldMap))
			$result['DUP_PARAM_FIELDS'] = array_keys($dupParamFieldMap);

		return $result;
	}

	/**
	 * Imports requisites for the company or contact.
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID for import
	 * @param string $dupControlType Duplicate control type ("NO_CONTROL", "REPLACE", "MERGE", "SKIP")
	 * @return Main\Result
	 */
	public function importParsedRequisites($entityTypeId, $entityId, $dupControlType)
	{
		$result = new Main\Result();

		foreach ($this->requisiteList as $requisiteFields)
		{
			$presetId = isset($requisiteFields['PRESET_ID']) ? (int)$requisiteFields['PRESET_ID'] : 0;

			if(!in_array($dupControlType, array('REPLACE', 'MERGE', 'SKIP'), true))
				$dupControlType = 'NO_CONTROL';
			$rqImportMode = 'MERGE';
			switch ($dupControlType)
			{
				case 'REPLACE':
					$rqImportMode = $dupControlType;
					break;
			}

			if (!EntityRequisite::checkEntityType($entityTypeId))
			{
				$result->addError(
					new Main\Error(
						GetMessage('CRM_RQ_IMP_HLPR_ERR_INVALID_ENTITY_TYPE'),
						self::ERR_INVALID_ENTITY_TYPE
					)
				);
				return $result;
			}

			if ($entityId <= 0)
			{
				$result->addError(
					new Main\Error(
						GetMessage('CRM_RQ_IMP_HLPR_ERR_INVALID_ENTITY_ID'),
						self::ERR_INVALID_ENTITY_ID
					)
				);
				return $result;
			}

			$requisite = EntityRequisite::getSingleInstance();
			if (!$requisite->validateEntityExists($entityTypeId, $entityId))
			{
				$errMsg = '';
				$errCode = 0;
				switch ($entityTypeId)
				{
					case \CCrmOwnerType::Company:
						$errMsg = GetMessage('CRM_RQ_IMP_HLPR_ERR_COMPANY_NOT_EXISTS', array('#ID#' => $entityId));
						$errCode = self::ERR_COMPANY_NOT_EXISTS;
						break;
					case \CCrmOwnerType::Contact:
						$errMsg = GetMessage('CRM_RQ_IMP_HLPR_ERR_CONTACT_NOT_EXISTS', array('#ID#' => $entityId));
						$errCode = self::ERR_CONTACT_NOT_EXISTS;
						break;
				}
				$result->addError(new Main\Error($errMsg, $errCode));
				return $result;
			}

			if (!EntityRequisite::checkUpdatePermissionOwnerEntity($entityTypeId, $entityId))
			{
				$errMsg = '';
				$errCode = 0;
				switch ($entityTypeId)
				{
					case \CCrmOwnerType::Company:
						$errMsg = GetMessage(
							'CRM_RQ_IMP_HLPR_ERR_ACCESS_DENIED_COMPANY_UPDATE',
							array('#ID#' => $entityId)
						);
						$errCode = self::ERR_ACCESS_DENIED_COMPANY_UPDATE;
						break;
					case \CCrmOwnerType::Contact:
						$errMsg = GetMessage(
							'CRM_RQ_IMP_HLPR_ERR_ACCESS_DENIED_CONTACT_UPDATE',
							array('#ID#' => $entityId)
						);
						$errCode = self::ERR_ACCESS_DENIED_CONTACT_UPDATE;
						break;
				}
				$result->addError(new Main\Error($errMsg, $errCode));
				return $result;
			}

			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
			if ($presetId === 0)
			{
				$presetId = EntityRequisite::getDefaultPresetId($entityTypeId);

				if ($presetId <= 0)
				{
					$result->addError(
						new Main\Error(
							GetMessage(
								'CRM_RQ_IMP_HLPR_ERR_DEF_IMP_PRESET_NOT_DEFINED',
								array('#ENTITY_TYPE_NAME_GENITIVE#' => $entityTypeName)
							),
							self::ERR_DEF_IMP_PRESET_NOT_DEFINED
						)
					);
					return $result;
				}
			}

			$presetId = (int)$presetId;
			if ($presetId <= 0)
			{
				$result->addError(
					new Main\Error(
						GetMessage('CRM_RQ_IMP_HLPR_ERR_INVALID_IMP_PRESET_ID'),
						self::ERR_INVALID_IMP_PRESET_ID
					)
				);
				return $result;
			}

			$preset = EntityPreset::getSingleInstance();
			$presetInfo = $preset->getById($presetId);
			$requisiteFieldMap = array(
				'ID' => true,
				'PRESET_ID' => true,
				'NAME' => true,
				'ACTIVE' => true,
				'SORT' => true
			);
			if (!is_array($presetInfo))
			{
				$result->addError(
					new Main\Error(
						GetMessage('CRM_RQ_IMP_HLPR_ERR_IMP_PRESET_NOT_EXISTS', array('#ID#' => $presetId)),
						self::ERR_IMP_PRESET_NOT_EXISTS
					)
				);
				return $result;
			}
			$presetName = EntityPreset::formatName($presetId, $presetInfo['NAME']);
			$countryId = (int)$presetInfo['COUNTRY_ID'];
			if (is_array($presetInfo['SETTINGS']))
			{
				$presetFieldsInfo = $preset->settingsGetFields($presetInfo['SETTINGS']);
				foreach ($presetFieldsInfo as $fieldInfo)
				{
					if (isset($fieldInfo['FIELD_NAME']) && !empty($fieldInfo['FIELD_NAME']))
						$requisiteFieldMap[$fieldInfo['FIELD_NAME']] = true;
				}
			}
			unset($preset, $presetHasAddress, $presetFieldsInfo, $fieldInfo);

			$rqRes = $requisite->getList(
				array(
					'select' => array_keys($requisiteFieldMap),
					'filter' => array(
						'=PRESET_ID' => $presetId,
						'=ENTITY_TYPE_ID' => $entityTypeId,
						'=ENTITY_ID' => $entityId
					)
				)
			);
			$rqFieldTypeInfoMap = null;
			$bankDetailFieldMap = null;
			$bdFieldTypeInfoMap = null;
			$rqComplianceFound = false;
			$bankDetail = EntityBankDetail::getSingleInstance();
			while($rqRow = $rqRes->fetch())
			{
				if($rqFieldTypeInfoMap === null)
				{
					$rqFieldTypeInfoMap = array();
					foreach ($requisite->getFormFieldsInfo($countryId) as $fieldName => $fieldInfo)
					{
						$rqFieldTypeInfoMap[$fieldName] = array(
							'type' => $fieldInfo['type'],
							'isUF' => $fieldInfo['isUF']
						);
					}
				}

				// load fields of existing requisite
				$exRequisiteFields = array();
				foreach (array_keys($requisiteFieldMap) as $fieldName)
				{
					if(isset($rqFieldTypeInfoMap[$fieldName]))
					{
						$fieldType = $rqFieldTypeInfoMap[$fieldName]['type'];
						$isUF = $rqFieldTypeInfoMap[$fieldName]['isUF'];

						if($fieldName === EntityRequisite::ADDRESS)
						{
							$value = array();
						}
						else
						{
							switch ($fieldType)
							{
								case 'integer':
									$value = isset($rqRow[$fieldName]) ? (int)$rqRow[$fieldName] : null;
									break;
								case 'boolean':
									$value = isset($rqRow[$fieldName]) ? (bool)$rqRow[$fieldName] : false;
									if($isUF)
										$value = (intval($value) > 0) ? 1 : 0;
									else
										$value = ($value === 'Y') ? 'Y' : 'N';
									break;
								case 'datetime':
									$value = isset($rqRow[$fieldName]) ? $rqRow[$fieldName] : '';
									if($value instanceof Main\Type\DateTime)
										$value = $value->toString();
									break;
								default:
									$value = isset($rqRow[$fieldName]) ? strval($rqRow[$fieldName]) : '';
							}
						}

						$exRequisiteFields[$fieldName] = $value;
					}
				}

				// check requisite compliance
				$dupCriterionFields = EntityRequisite::getDuplicateCriterionFieldsMap();
				$dupCriterionFields = isset($dupCriterionFields[$countryId]) ?
					$dupCriterionFields[$countryId] : array();
				$compKeyFields = array_merge(array('NAME'), $dupCriterionFields);
				unset($dupCriterionFields);
				$rqComplianceByKeyField = false;
				foreach ($compKeyFields as $fieldName)
				{
					if (isset($requisiteFields[$fieldName]) && isset($exRequisiteFields[$fieldName])
						&& $requisiteFields[$fieldName] === $exRequisiteFields[$fieldName])
					{
						$rqComplianceByKeyField = true;
						$rqComplianceFound = true;
						break;
					}
				}

				if ($rqComplianceByKeyField)
				{
					// load addresses of existing requisite
					$requisiteId = (int)$rqRow['ID'];
					$exRequisiteAddresses = EntityRequisite::getAddresses($requisiteId);

					// update requisite fields
					$requisiteFieldsToUpdate = array();
					foreach (array_keys($requisiteFieldMap) as $fieldName)
					{
						// $dupControlType may be only 'REPLACE' or 'MERGE'
						if ($dupControlType === 'REPLACE')
						{
							if (array_key_exists($fieldName, $requisiteFields))
							{
								if (!array_key_exists($fieldName, $exRequisiteFields) ||
									$exRequisiteFields[$fieldName] !== $requisiteFields[$fieldName])
								{
									$requisiteFieldsToUpdate[$fieldName] = $requisiteFields[$fieldName];
								}
							}
						}
						else if ($dupControlType === 'MERGE')
						{
							if (!array_key_exists($fieldName, $exRequisiteFields)
								|| $exRequisiteFields[$fieldName] === null
								|| (is_string($exRequisiteFields[$fieldName])
									&& strlen($exRequisiteFields[$fieldName]) <= 0)
								|| (!is_int($exRequisiteFields[$fieldName])
									&& empty($exRequisiteFields[$fieldName])))
							{
								if (isset($requisiteFields[$fieldName])
									&& (is_int($requisiteFields[$fieldName])
										|| (is_string($requisiteFields[$fieldName]
											&& strlen(is_string($requisiteFields[$fieldName]) > 0)))
										|| !empty($requisiteFields[$fieldName])))
								{
									$requisiteFieldsToUpdate[$fieldName] = $requisiteFields[$fieldName];
								}
							}
						}
					}
					if (isset($requisiteFieldsToUpdate['ID']))
						unset($requisiteFieldsToUpdate['ID']);
					if (!empty($requisiteFieldsToUpdate))
					{
						$errorOccured = false;
						$errMsg = '';
						try
						{
							$rqUpdRes = $requisite->update($requisiteId, $requisiteFieldsToUpdate);
						}
						catch (Main\SystemException $e)
						{
							$errorOccured = true;
							$errMsg = $e->getMessage();
						}
						if (!$errorOccured && !$rqUpdRes->isSuccess())
						{
							$errorOccured = true;
							$errMsg = $this->getErrorMessage($rqUpdRes);
						}
						unset($rqUpdRes);
						if ($errorOccured)
						{
							$result->addError(
								new Main\Error(
									GetMessage(
										'CRM_RQ_IMP_HLPR_ERR_UPDATE_REQUISITE',
										array(
											'#ENTITY_TYPE_NAME_GENITIVE#' => GetMessage(
												'CRM_RQ_IMP_HLPR_ERR_'.$entityTypeName.'_GENITIVE'
											),
											'#ID#' => $entityId,
										).': '.$errMsg
									),
									self::ERR_UPDATE_REQUISITE
								)
							);
							return $result;
						}
						unset($errorOccured, $errMsg);
					}

					// update addresses
					$requisiteAddresses = array();
					$rqAddrTypeInfos = RequisiteAddress::getTypeInfos();
					$addressFields = array(
						'ADDRESS_1',
						'ADDRESS_2',
						'CITY',
						'POSTAL_CODE',
						'REGION',
						'PROVINCE',
						'COUNTRY',
						'COUNTRY_CODE'
					);
					$rqAddrTypes = array_keys($rqAddrTypeInfos);
					if (is_array($requisiteFields)
						&& is_array($requisiteFields[EntityRequisite::ADDRESS])
						&& !empty($requisiteFields[EntityRequisite::ADDRESS]))
					{
						foreach ($requisiteFields[EntityRequisite::ADDRESS] as $addrTypeId => $address)
						{
							if (in_array($addrTypeId, $rqAddrTypes, true) && !RequisiteAddress::isEmpty($address))
							{
								foreach ($addressFields as $fieldName)
								{
									$requisiteAddresses[$addrTypeId][$fieldName] =
										isset($address[$fieldName]) ? $address[$fieldName] : null;
								}
							}
						}
					}
					foreach($requisiteAddresses as $addrTypeId => $address)
					{
						// $rqImportMode may be only 'REPLACE' or 'MERGE'
						if(!isset($exRequisiteAddresses[$addrTypeId])
							|| RequisiteAddress::isEmpty($exRequisiteAddresses[$addrTypeId])
							|| ($rqImportMode === 'REPLACE'
								&&  !RequisiteAddress::areEquals(
									$requisiteAddresses[$addrTypeId], $exRequisiteAddresses[$addrTypeId]
								)))
						{
							RequisiteAddress::register(
								\CCrmOwnerType::Requisite,
								$requisiteId,
								$addrTypeId,
								$address
							);
						}
					}
					unset($requisiteAddresses);

					// update bank details
					if (is_array($requisiteFields['BANK_DETAILS']) && count($requisiteFields['BANK_DETAILS']) > 0)
					{
						foreach ($requisiteFields['BANK_DETAILS'] as $bankDetailFields)
						{
							// load bank details of existing requisite
							if ($bankDetailFieldMap === null)
							{
								$bankDetailFieldMap = array(
									'ID' => true,
									'NAME' => true,
									'COUNTRY_ID' => true,
									'ACTIVE' => true,
									'SORT' => true
								);
								$bankDetailRqFieldsByCountry = $bankDetail->getRqFieldByCountry();
								if (is_array($bankDetailRqFieldsByCountry[$countryId]))
								{
									foreach ($bankDetailRqFieldsByCountry[$countryId] as $fieldName)
										$bankDetailFieldMap[$fieldName] = true;
								}
								$bankDetailFieldMap['COMMENTS'] = true;
								unset($bankDetailRqFieldsByCountry);
							}
							$bdRes = $bankDetail->getList(
								array(
									'select' => array_keys($bankDetailFieldMap),
									'filter' => array(
										'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
										'=ENTITY_ID' => $requisiteId
									)
								)
							);
							$bdComplianceFound = false;
							while($bdRow = $bdRes->fetch())
							{
								$bankDetailId = (int)$bdRow['ID'];
								if($bdFieldTypeInfoMap === null)
								{
									$bdFieldTypeInfoMap = array();
									foreach ($bankDetail->getFormFieldsInfo($countryId) as $fieldName => $fieldInfo)
									{
										$bdFieldTypeInfoMap[$fieldName] = array(
											'type' => $fieldInfo['type'],
											'isUF' => $fieldInfo['isUF']
										);
									}
								}

								// load fields of existing bank details
								$exBankDetailFields = array();
								foreach (array_keys($bankDetailFieldMap) as $fieldName)
								{
									if(isset($bdFieldTypeInfoMap[$fieldName]))
									{
										$fieldType = $bdFieldTypeInfoMap[$fieldName]['type'];
										$isUF = $bdFieldTypeInfoMap[$fieldName]['isUF'];

										switch ($fieldType)
										{
											case 'integer':
												$value = isset($bdRow[$fieldName]) ? (int)$bdRow[$fieldName] : null;
												break;
											case 'boolean':
												$value = isset($bdRow[$fieldName]) ? (bool)$bdRow[$fieldName] : false;
												if($isUF)
													$value = (intval($value) > 0) ? 1 : 0;
												else
													$value = ($value === 'Y') ? 'Y' : 'N';
												break;
											case 'datetime':
												$value = isset($bdRow[$fieldName]) ? $bdRow[$fieldName] : '';
												if($value instanceof Main\Type\DateTime)
													$value = $value->toString();
												break;
											default:
												$value = isset($bdRow[$fieldName]) ? strval($bdRow[$fieldName]) : '';
										}

										$exBankDetailFields[$fieldName] = $value;
									}
								}

								// check bank detail compliance
								$dupCriterionFields = EntityBankDetail::getDuplicateCriterionFieldsMap();
								$dupCriterionFields = isset($dupCriterionFields[$countryId]) ?
									$dupCriterionFields[$countryId] : array();
								$compKeyFields = array_merge(array('NAME'), $dupCriterionFields);
								unset($dupCriterionFields);
								$bdComplianceByKeyField = false;
								foreach ($compKeyFields as $fieldName)
								{
									if (isset($bankDetailFields[$fieldName]) && isset($exBankDetailFields[$fieldName])
										&& $bankDetailFields[$fieldName] === $exBankDetailFields[$fieldName])
									{
										$bdComplianceByKeyField = true;
										$bdComplianceFound = true;
										break;
									}
								}

								if ($bdComplianceByKeyField)
								{
									// update bank detail fields
									$bankdetailFieldsToUpdate = array();
									foreach (array_keys($bankDetailFieldMap) as $fieldName)
									{
										// $dupControlType may be only 'REPLACE' or 'MERGE'
										if ($dupControlType === 'REPLACE')
										{
											if (array_key_exists($fieldName, $bankDetailFields))
											{
												if (!array_key_exists($fieldName, $exBankDetailFields) ||
													$exBankDetailFields[$fieldName] !== $bankDetailFields[$fieldName])
												{
													$bankdetailFieldsToUpdate[$fieldName]
														= $bankDetailFields[$fieldName];
												}
											}
										}
										else if ($dupControlType === 'MERGE')
										{
											if (!array_key_exists($fieldName, $exBankDetailFields)
												|| $exBankDetailFields[$fieldName] === null
												|| (is_string($exBankDetailFields[$fieldName])
													&& strlen($exBankDetailFields[$fieldName]) <= 0)
												|| (!is_int($exBankDetailFields[$fieldName])
													&& empty($exBankDetailFields[$fieldName])))
											{
												if (isset($bankDetailFields[$fieldName])
													&& (is_int($bankDetailFields[$fieldName])
														|| (is_string($bankDetailFields[$fieldName]
															&& strlen(is_string($bankDetailFields[$fieldName]) > 0)))
														|| !empty($bankDetailFields[$fieldName])))
												{
													$bankdetailFieldsToUpdate[$fieldName] =
														$bankDetailFields[$fieldName];
												}
											}
										}
									}
									if (isset($bankdetailFieldsToUpdate['ID']))
										unset($bankdetailFieldsToUpdate['ID']);
									if (!empty($bankdetailFieldsToUpdate))
									{
										$errorOccured = false;
										$errMsg = '';
										try
										{
											$bdUpdRes = $bankDetail->update($bankDetailId, $bankdetailFieldsToUpdate);
										}
										catch (Main\SystemException $e)
										{
											$errorOccured = true;
											$errMsg = $e->getMessage();
										}
										if (!$errorOccured && !$bdUpdRes->isSuccess())
										{
											$errorOccured = true;
											$errMsg = $this->getErrorMessage($bdUpdRes);
										}
										unset($bdUpdRes);
										if ($errorOccured)
										{
											$result->addError(
												new Main\Error(
													GetMessage(
														'CRM_RQ_IMP_HLPR_ERR_UPDATE_BANK_DETAIL',
														array(
															'#ENTITY_TYPE_NAME_GENITIVE#' => GetMessage(
																'CRM_RQ_IMP_HLPR_ERR_'.$entityTypeName.'_GENITIVE'
															),
															'#ID#' => $entityId,
														).': '.$errMsg
													),
													self::ERR_UPDATE_BANK_DETAIL
												)
											);
											return $result;
										}
										unset($errorOccured, $errMsg);
									}
								}
							}
							if (!$bdComplianceFound)
							{
								$bankDetailFieldsToAdd = array(
									'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
									'ENTITY_ID' => $requisiteId,
									'COUNTRY_ID' => $countryId,
									'ACTIVE' => 'Y',
									'SORT' => 500
								);
								foreach (array_keys($bankDetailFieldMap) as $fieldName)
								{
									if (isset($bankDetailFields[$fieldName]))
										$bankDetailFieldsToAdd[$fieldName] = $bankDetailFields[$fieldName];
								}
								if (isset($bankDetailFieldsToAdd['ID']))
									unset($bankDetailFieldsToAdd['ID']);
								$errorOccured = false;
								$errMsg = '';
								try
								{
									$bdAddRes = $bankDetail->add($bankDetailFieldsToAdd);
								}
								catch (Main\SystemException $e)
								{
									$errorOccured = true;
									$errMsg = $e->getMessage();
								}
								unset($bankDetailFieldsToAdd);
								if(!$errorOccured && !$bdAddRes->isSuccess())
								{
									$errorOccured = true;
									$errMsg = $this->getErrorMessage($bdAddRes);
								}
								unset($bdAddRes);
								if ($errorOccured)
								{
									$result->addError(
										new Main\Error(
											GetMessage(
												'CRM_RQ_IMP_HLPR_ERR_CREATE_BANK_DETAIL',
												array(
													'#ENTITY_TYPE_NAME_GENITIVE#' => GetMessage(
														'CRM_RQ_IMP_HLPR_ERR_'.$entityTypeName.'_GENITIVE'
													),
													'#ID#' => $entityId,
												)
											).': '.$errMsg,
											self::ERR_CREATE_BANK_DETAIL
										)
									);
									return $result;
								}
								unset($errorOccured, $errMsg);
							}
							unset($bdRes, $bdRow);
						}
					}
				}
			}
			if (!$rqComplianceFound)
			{
				$requisiteFieldsToAdd = array(
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $entityId,
					'PRESET_ID' => $presetId,
					'NAME' => $presetName,
					'ACTIVE' => 'Y',
					'SORT' => 500
				);
				foreach (array_keys($requisiteFieldMap) as $fieldName)
				{
					if (isset($requisiteFields[$fieldName]))
						$requisiteFieldsToAdd[$fieldName] = $requisiteFields[$fieldName];
				}
				if (isset($requisiteFieldsToAdd['ID']))
					unset($requisiteFieldsToAdd['ID']);
				$errorOccured = false;
				$errMsg = '';
				try
				{
					$rqAddRes = $requisite->add($requisiteFieldsToAdd);
				}
				catch (Main\SystemException $e)
				{
					$errorOccured = true;
					$errMsg = $e->getMessage();
				}
				if(!$errorOccured && !$rqAddRes->isSuccess())
				{
					$errorOccured = true;
					$errMsg = $this->getErrorMessage($rqAddRes);
				}
				if ($errorOccured)
				{
					$result->addError(
						new Main\Error(
							GetMessage(
								'CRM_RQ_IMP_HLPR_ERR_CREATE_REQUISITE',
								array(
									'#ENTITY_TYPE_NAME_GENITIVE#' => GetMessage(
										'CRM_RQ_IMP_HLPR_ERR_'.$entityTypeName.'_GENITIVE'
									),
									'#ID#' => $entityId,
								)
							).': '.$errMsg,
							self::ERR_CREATE_REQUISITE
						)
					);
					return $result;
				}
				$requisiteId = $rqAddRes->getId();
				unset($rqAddRes, $errorOccured, $errMsg);

				if (is_array($requisiteFields['BANK_DETAILS']) && count($requisiteFields['BANK_DETAILS']) > 0)
				{
					foreach ($requisiteFields['BANK_DETAILS'] as $bankDetailFields)
					{
						if ($bankDetailFieldMap === null)
						{
							$bankDetailFieldMap = array(
								'ID' => true,
								'NAME' => true,
								'COUNTRY_ID' => true,
								'ACTIVE' => true,
								'SORT' => true
							);
							$bankDetailRqFieldsByCountry = $bankDetail->getRqFieldByCountry();
							if (is_array($bankDetailRqFieldsByCountry[$countryId]))
							{
								foreach ($bankDetailRqFieldsByCountry[$countryId] as $fieldName)
									$bankDetailFieldMap[$fieldName] = true;
							}
							$bankDetailFieldMap['COMMENTS'] = true;
							unset($bankDetailRqFieldsByCountry);
						}
						$bankDetailFieldsToAdd = array(
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
							'ENTITY_ID' => $requisiteId,
							'COUNTRY_ID' => $countryId,
							'ACTIVE' => 'Y',
							'SORT' => 500
						);
						foreach (array_keys($bankDetailFieldMap) as $fieldName)
						{
							if (isset($bankDetailFields[$fieldName]))
								$bankDetailFieldsToAdd[$fieldName] = $bankDetailFields[$fieldName];
						}
						if (isset($bankDetailFieldsToAdd['ID']))
							unset($bankDetailFieldsToAdd['ID']);
						$errorOccured = false;
						$errMsg = '';
						try
						{
							$bdAddRes = $bankDetail->add($bankDetailFieldsToAdd);
						}
						catch (Main\SystemException $e)
						{
							$errorOccured = true;
							$errMsg = $e->getMessage();
						}
						unset($bankDetailFieldsToAdd);
						if(!$errorOccured && !$bdAddRes->isSuccess())
						{
							$errorOccured = true;
							$errMsg = $this->getErrorMessage($bdAddRes);
						}
						unset($bdAddRes);
						if ($errorOccured)
						{
							$result->addError(
								new Main\Error(
									GetMessage(
										'CRM_RQ_IMP_HLPR_ERR_CREATE_BANK_DETAIL',
										array(
											'#ENTITY_TYPE_NAME_GENITIVE#' => GetMessage(
												'CRM_RQ_IMP_HLPR_ERR_'.$entityTypeName.'_GENITIVE'
											),
											'#ID#' => $entityId,
										)
									).': '.$errMsg,
									self::ERR_CREATE_BANK_DETAIL
								)
							);
							return $result;
						}
						unset($errorOccured, $errMsg);
					}
				}
			}
			unset($rqRes, $rqRow);
		}

		return $result;
	}

	protected function parseRequisiteDataRow(&$context, $row)
	{
		$result = new Main\Result();

		$presense = $this->getPresense($row);

		// parse requisite fields
		if (isset($presense['byGroup']['requisite']) && $presense['byGroup']['requisite'])
		{
			$res = $this->parseRequisiteKey($row);
			if (!$res->isSuccess())
			{
				return $res;
			}
			$res = $res->getData();
			$requisiteKey = $res[0];
			unset($res);

			if ($context['rowNumber'] === 1 && strlen($requisiteKey) <= 0)
			{
				$result->addError(
					new Main\Error(
						Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_EMPTY_RQ_KEY_FIELDS'),
						self::ERR_EMPTY_RQ_KEY_FIELDS
					)
				);

				return $result;
			}

			if ($requisiteKey !== '' && $requisiteKey !== $context['requisiteKey'])
			{
				$res = $this->associatePreset($row);
				if (!$res->isSuccess())
				{
					return $res;
				}
				$presetInfo = $res->getData();

				$res = $this->parseRequisiteFields($row, $presetInfo);
				if (!$res->isSuccess())
				{
					return $res;
				}
				$requisiteFields = $res->getData();

				if (!isset($requisiteFields['NAME']) || strlen($requisiteFields['NAME']) <= 0)
				{
					$result->addError(
						new Main\Error(
							Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_RQ_NAME_IS_NOT_SET'),
							self::ERR_RQ_NAME_IS_NOT_SET
						)
					);

					return $result;
				}

				$this->requisiteList[++$context['requisiteIndex']] = $requisiteFields;
				$context['requisiteKey'] = $requisiteKey;
				$context['presetInfo'] = $presetInfo;
				$context['bankDetailIndex'] = -1;
				unset($res, $requisiteFields);
			}
		}

		// parse address fields
		if (isset($presense['byGroup']['address']) && $presense['byGroup']['address']
			&& $context['requisiteIndex'] >= 0 && is_array($context['presetInfo']))
		{
			$res = $this->parseAddressFields($row, $context['presetInfo']);
			if (!$res->isSuccess())
			{
				return $res;
			}
			$addressFields = $res->getData();

			if (!isset($addressFields['TYPE_ID']))
			{
				$result->addError(
					new Main\Error(
						Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_ADDRESS_TYPE_IS_NOT_SET'),
						self::ERR_ADDRESS_TYPE_IS_NOT_SET
					)
				);

				return $result;
			}

			$addressType = $addressFields['TYPE_ID'];
			unset($addressFields['TYPE_ID']);

			if (is_array($this->requisiteList[$context['requisiteIndex']][EntityRequisite::ADDRESS]))
			{
				if (isset($this->requisiteList[$context['requisiteIndex']][EntityRequisite::ADDRESS][$addressType]))
				{
					$result->addError(
						new Main\Error(
							Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_ADDRESS_TYPE_ALREADY_EXISTS'),
							self::ERR_ADDRESS_TYPE_ALREADY_EXISTS
						)
					);

					return $result;
				}
			}
			else
			{
				$this->requisiteList[$context['requisiteIndex']][EntityRequisite::ADDRESS] = array();
			}

			$this->requisiteList[$context['requisiteIndex']][EntityRequisite::ADDRESS][$addressType] = $addressFields;
		}

		// parse bank detail fields
		if (isset($presense['byGroup']['bankDetail']) && $presense['byGroup']['bankDetail']
			&& $context['requisiteIndex'] >= 0 && is_array($context['presetInfo']))
		{
			$res = $this->parseBankDetailKey($row);
			if (!$res->isSuccess())
			{
				return $res;
			}
			$res = $res->getData();
			$bankDetailKey = $res[0];
			unset($res);

			if (strlen($bankDetailKey) <= 0)
			{
				$result->addError(
					new Main\Error(
						Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_EMPTY_BD_KEY_FIELDS'),
						self::ERR_EMPTY_BD_KEY_FIELDS
					)
				);

				return $result;
			}

			$res = $this->parseBankDetailFields($row, $context['presetInfo']);
			if (!$res->isSuccess())
			{
				return $res;
			}

			$bankDetailFields = $res->getData();

			if (!isset($bankDetailFields['NAME']) || strlen($bankDetailFields['NAME']) <= 0)
			{
				$result->addError(
					new Main\Error(
						Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_BD_NAME_IS_NOT_SET'),
						self::ERR_BD_NAME_IS_NOT_SET
					)
				);

				return $result;
			}

			if (!is_array($this->requisiteList[$context['requisiteIndex']]['BANK_DETAILS']))
				$this->requisiteList[$context['requisiteIndex']]['BANK_DETAILS'] = array();
			$this->requisiteList[$context['requisiteIndex']]['BANK_DETAILS'][++$context['bankDetailIndex']] =
				$bankDetailFields;
			unset($res, $bankDetailFields);
		}

		return $result;
	}

	protected function getPresense($row)
	{
		$result = array(
			'byGroup' => array(),
			'byCountry' => array()
		);

		foreach (array_keys($this->headerGroupCountryIdMap) as $groupName)
		{
			foreach ($this->headerGroupCountryIdMap[$groupName] as $countryId => $headerMap)
			{
				foreach (array_keys($headerMap) as $headerId)
				{
					$index = $this->headerIndex[$headerId];
					if (isset($row[$index]) && is_string($row[$index]) && strlen($row[$index]) > 0
						&& !isset($result['byCountry'][$groupName][$countryId]))
					{
						if (!isset($result['byGroup'][$groupName]))
							$result['byGroup'][$groupName] = true;
						$result['byCountry'][$groupName][$countryId] = true;
						break;
					}
				}
			}
		}

		return $result;
	}

	protected function associatePreset($row)
	{
		$result = new Main\Result();

		$countryId = 0;
		$presetId = 0;

		// check country by id
		$allowedCountries = EntityRequisite::getAllowedRqFieldCountries();
		if (isset($this->headerGroupCountryIdMap['requisite'][0][$this->rqFieldPrefix.'PRESET_COUNTRY_ID'])
			&& isset($row[$this->headerIndex[$this->rqFieldPrefix.'PRESET_COUNTRY_ID']]))
		{
			$value = $row[$this->headerIndex[$this->rqFieldPrefix.'PRESET_COUNTRY_ID']];
			if (is_string($value) && strlen($value) > 0)
			{
				$value = (int)$value;
				if ($value > 0 && in_array($value, $allowedCountries, true))
					$countryId = $value;
			}
		}

		// check country by name
		if ($countryId <= 0)
		{
			$countryList = EntityPreset::getCountryList();
			if (isset($this->headerGroupCountryIdMap['requisite'][0][$this->rqFieldPrefix.'PRESET_COUNTRY_NAME'])
				&& isset($row[$this->headerIndex[$this->rqFieldPrefix.'PRESET_COUNTRY_NAME']]))
			{
				$value = $row[$this->headerIndex[$this->rqFieldPrefix.'PRESET_COUNTRY_NAME']];
				if (is_string($value))
				{
					$value = trim($value);
					if (strlen($value) > 0)
					{
						$value = array_search($value, $countryList, true);
						if ($value !== false && in_array($value, $allowedCountries, true))
							$countryId = $value;
					}
				}
			}
		}

		// associate preset
		if ($this->assocPreset)
		{
			if ($this->assocPresetById)
			{
				if (isset($this->headerGroupCountryIdMap['requisite'][0][$this->rqFieldPrefix.'PRESET_ID'])
					&& isset($row[$this->headerIndex[$this->rqFieldPrefix.'PRESET_ID']]))
				{
					$value = $row[$this->headerIndex[$this->rqFieldPrefix.'PRESET_ID']];
					if (is_string($value) && strlen($value) > 0)
					{
						$value = (int)trim($value);
						if ($value < 0)
							$value = 0;
						$this->updatePresetCacheById($value);
						$presetId = $value;
					}
				}
			}
			else
			{
				if (isset($this->headerGroupCountryIdMap['requisite'][0][$this->rqFieldPrefix.'PRESET_NAME'])
					&& isset($row[$this->headerIndex[$this->rqFieldPrefix.'PRESET_NAME']]))
				{
					$value = $row[$this->headerIndex[$this->rqFieldPrefix.'PRESET_NAME']];
					if (is_string($value))
					{
						if (strlen($value) > 0)
						{
							if (strlen($value) > 255)
								$value = substr($value, 0, 255);
							$this->updatePresetCacheByName($value, $countryId);
							if (is_array(self::$presetCacheByName[$value][$countryId])
								&& count(self::$presetCacheByName[$value][$countryId]) > 0)
							{
								$presetId = self::$presetCacheByName[$value][$countryId][0]['ID'];
							}
						}
					}
				}
			}
			if (!is_array(self::$presetCacheById[$presetId]) && $this->useDefPreset)
			{
				$value = $this->defPresetId;
				if ($value < 0)
					$value = 0;
				$this->updatePresetCacheById($value);
				$presetId = $value;
			}
		}
		else
		{
			$value = $this->defPresetId;
			if ($value < 0)
				$value = 0;
			$this->updatePresetCacheById($value);
			$presetId = $value;
		}

		if (is_array(self::$presetCacheById[$presetId]) && $countryId > 0
			&& $countryId !== self::$presetCacheById[$presetId]['COUNTRY_ID'])
		{
			$presetId = 0;
		}

		if (!is_array(self::$presetCacheById[$presetId]))
		{
			$result->addError(
				new Main\Error(
					Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_PRESET_ASSOC'),
					self::ERR_PRESET_ASSOC
				)
			);
		}
		else
		{
			$result->setData(self::$presetCacheById[$presetId]);
		}

		return $result;
	}
	
	protected function updatePresetCacheById($presetId)
	{
		if ($presetId < 0)
			$presetId = 0;
		
		if (!isset(self::$presetCacheById[$presetId]))
		{
			if ($presetId > 0)
			{
				$preset = EntityPreset::getSingleInstance();
				$res = $preset->getList(
					array(
						'filter' => array('=ID' => $presetId),
						'select' => array('ID', 'COUNTRY_ID', 'SETTINGS')
					)
				);
				if ($row = $res->fetch())
				{
					$id = (int)$row['ID'];
					$presetFieldMap = array();
					if (is_array($row['SETTINGS']))
					{
						$presetFieldsInfo = $preset->settingsGetFields($row['SETTINGS']);
						foreach ($presetFieldsInfo as $fieldInfo)
						{
							if (isset($fieldInfo['FIELD_NAME']) && !empty($fieldInfo['FIELD_NAME']))
								$presetFieldMap[$fieldInfo['FIELD_NAME']] = true;
						}
						unset($presetFieldsInfo, $fieldInfo);
					}
					self::$presetCacheById[$id] = array(
						'ID' => $id,
						'COUNTRY_ID' => (int)$row['COUNTRY_ID'],
						'FIELD_MAP' => $presetFieldMap
					);
				}
			}
			if (!isset(self::$presetCacheById[$presetId]))
			{
				self::$presetCacheById[$presetId] = false;
			}
		}
	}

	protected function updatePresetCacheByName($presetName, $countryId)
	{
		if (!is_int($countryId))
			$countryId = (int)$countryId;
		if ($countryId < 0)
			$countryId = 0;
		if (!is_string($presetName))
			$presetName = strval($presetName);
		if (strlen($presetName) > 255)
			$presetName = substr($presetName, 0, 255);

		if (!isset(self::$presetCacheByName[$presetName][$countryId]))
		{
			if (strlen($presetName) > 0)
			{
				$preset = EntityPreset::getSingleInstance();
				$presetList = array();
				$filter = array('=NAME' => $presetName);
				if ($countryId > 0)
					$filter['=COUNTRY_ID'] = $countryId;
				$res = $preset->getList(
					array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => $filter,
						'select' => array('ID', 'COUNTRY_ID', 'SETTINGS')
					)
				);
				while ($row = $res->fetch())
				{
					$id = (int)$row['ID'];
					$presetFieldMap = array();
					if (is_array($row['SETTINGS']))
					{
						$presetFieldsInfo = $preset->settingsGetFields($row['SETTINGS']);
						foreach ($presetFieldsInfo as $fieldInfo)
						{
							if (isset($fieldInfo['FIELD_NAME']) && !empty($fieldInfo['FIELD_NAME']))
								$presetFieldMap[$fieldInfo['FIELD_NAME']] = true;
						}
						unset($presetFieldsInfo, $fieldInfo);
					}
					$presetInfo = array(
						'ID' => $id,
						'COUNTRY_ID' => (int)$row['COUNTRY_ID'],
						'FIELD_MAP' => $presetFieldMap
					);
					$presetList[] = $presetInfo;
					if (!isset(self::$presetCacheById[$id]))
						self::$presetCacheById[$id] = $presetInfo;
				}
				if ($countryId <= 0)
				{
					$currentCountryId = EntityPreset::getCurrentCountryId();
					$allowedCountries = EntityRequisite::getAllowedRqFieldCountries();
					if ($currentCountryId > 0 && in_array($currentCountryId, $allowedCountries, true))
					{
						$upperPresets = array();

						foreach ($presetList as $key => $row)
						{
							if ($row['COUNTRY_ID'] === $currentCountryId)
							{
								$upperPresets[] = $row;
								unset($presetList[$key]);
							}
						}
						if (count($upperPresets) > 0)
							$presetList = array_merge($upperPresets, $presetList);
					}
				}
				if (count($presetList) > 0)
					self::$presetCacheByName[$presetName][$countryId] = $presetList;
				unset($presetList);
			}
			if (!isset(self::$presetCacheByName[$presetName][$countryId]))
			{
				self::$presetCacheByName[$presetName][$countryId] = false;
			}
		}
	}

	protected function isPresetInCache($presetId)
	{
		return is_array(self::$presetCacheById[$presetId]);
	}

	protected function getCachedPresetInfo($presetId)
	{
		return isset(self::$presetCacheById[$presetId]) ? self::$presetCacheById[$presetId] : null;
	}

	protected function parseRequisiteFields($row, $presetInfo)
	{
		$result = new Main\Result();

		$requisiteFields = array();

		$countryId = $presetInfo['COUNTRY_ID'];
		$skipFieldsMap = array(
			$this->rqFieldPrefix.'ID' => true,
			$this->rqFieldPrefix.'PRESET_NAME' => true,
			$this->rqFieldPrefix.'PRESET_COUNTRY_ID' => true,
			$this->rqFieldPrefix.'PRESET_COUNTRY_NAME' => true
		);

		$headerIds = array();
		if (is_array($this->headerGroupCountryIdMap['requisite'][0]))
			$headerIds = array_keys($this->headerGroupCountryIdMap['requisite'][0]);
		if (is_array($this->headerGroupCountryIdMap['requisite'][$countryId]))
			$headerIds = array_merge($headerIds, array_keys($this->headerGroupCountryIdMap['requisite'][$countryId]));
		foreach ($headerIds as $headerId)
		{
			if (isset($skipFieldsMap[$headerId]))
				continue;

			$fieldName = '';
			$fieldCountryId = 0;
			$fieldType = 'string';
			$isUF = false;
			if (is_array($this->headerById[$headerId])
				&& isset($this->headerById[$headerId]['field'])
				&& isset($this->headerById[$headerId]['countryId'])
				&& $this->headerById[$headerId]['fieldType'])
			{
				$fieldName = $this->headerById[$headerId]['field'];
				$fieldCountryId = (int)$this->headerById[$headerId]['countryId'];
				$fieldType = $this->headerById[$headerId]['fieldType'];
				$isUF = $this->headerById[$headerId]['isUF'];
			}
			if (!is_string($fieldName) || strlen($fieldName) <= 0
				|| ($fieldCountryId > 0 && !isset($presetInfo['FIELD_MAP'][$fieldName])))
			{
				continue;
			}

			if (isset($row[$this->headerIndex[$headerId]]))
			{
				if ($fieldName === 'PRESET_ID')
				{
					$value = $presetInfo['ID'];
				}
				else
				{
					$value = $row[$this->headerIndex[$headerId]];
				}

				if ($fieldType === 'integer')
				{
					$value = (int)$value;
				}
				else if ($fieldType === 'boolean')
				{
					$value = ToUpper($value);
					$yesStr = ToUpper(Loc::getMessage('MAIN_YES'));
					$value = ($value === 'Y' || $value === $yesStr || $value == 1) ? 'Y' : 'N';
					if ($isUF)
						$value = $value === 'Y' ? 1 : 0;
				}
				else if ($fieldType === 'Address')
				{
					$value = array();
				}

				$requisiteFields[$fieldName] = $value;
			}
		}

		$result->setData($requisiteFields);

		return $result;
	}

	protected function getAddressTypeList()
	{
		if (!is_array(self::$addressTypeList))
		{
			$addressTypeList = array();
			foreach(RequisiteAddress::getClientTypeInfos() as $typeInfo)
				$addressTypeList[$typeInfo['id']] = $typeInfo['name'];
			self::$addressTypeList = $addressTypeList;
		}

		return self::$addressTypeList;
	}

	protected function parseAddressFields($row, $presetInfo)
	{
		$result = new Main\Result();

		$addressFields = array();

		$countryId = $presetInfo['COUNTRY_ID'];

		$headerIds = array();
		if (is_array($this->headerGroupCountryIdMap['address'][$countryId]))
			$headerIds = array_keys($this->headerGroupCountryIdMap['address'][$countryId]);
		foreach ($headerIds as $headerId)
		{
			$fieldName = '';
			if (is_array($this->headerById[$headerId])
				&& isset($this->headerById[$headerId]['field'])
				&& isset($this->headerById[$headerId]['countryId'])
				&& $this->headerById[$headerId]['fieldType'])
			{
				$fieldName = $this->headerById[$headerId]['field'];
			}
			if (!is_string($fieldName) || strlen($fieldName) <= 0)
			{
				continue;
			}

			if (isset($row[$this->headerIndex[$headerId]]))
			{
				$value = $row[$this->headerIndex[$headerId]];

				if ($fieldName === 'TYPE')
				{
					$isIncorrectAddressType = false;
					$addressTypeList = $this->getAddressTypeList();
					if (is_numeric($value))
					{
						$value = (int)$value;
						if (isset($addressTypeList[$value]))
							$addressFields['TYPE_ID'] = $value;
						else
							$isIncorrectAddressType = true;
					}
					else
					{
						$value = array_search($value, $addressTypeList, true);
						if ($value !== false)
							$addressFields['TYPE_ID'] = $value;
						else
							$isIncorrectAddressType = true;
					}
					if ($isIncorrectAddressType)
					{
						$result->addError(
							new Main\Error(
								Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_UNKNOWN_ADDRESS_TYPE'),
								self::ERR_UNKNOWN_ADDRESS_TYPE
							)
						);
					}
				}
				else
				{
					$addressFields[$fieldName] = $value;
				}
			}
		}

		$result->setData($addressFields);

		return $result;
	}

	protected function parseBankDetailFields($row, $presetInfo)
	{
		$result = new Main\Result();

		$bankDetailFields = array();
		$countryId = $presetInfo['COUNTRY_ID'];
		$skipFieldsMap = array(
			$this->bdFieldPrefix.'ID' => true
		);
		$headerIds = array();
		if (is_array($this->headerGroupCountryIdMap['bankDetail'][0]))
			$headerIds = array_keys($this->headerGroupCountryIdMap['bankDetail'][0]);
		if (is_array($this->headerGroupCountryIdMap['bankDetail'][$countryId]))
			$headerIds = array_merge($headerIds, array_keys($this->headerGroupCountryIdMap['bankDetail'][$countryId]));
		foreach ($headerIds as $headerId)
		{
			if (isset($skipFieldsMap[$headerId]))
				continue;

			$fieldName = '';
			$fieldType = 'string';
			if (is_array($this->headerById[$headerId])
				&& isset($this->headerById[$headerId]['field'])
				&& isset($this->headerById[$headerId]['countryId'])
				&& $this->headerById[$headerId]['fieldType'])
			{
				$fieldName = $this->headerById[$headerId]['field'];
				$fieldType = $this->headerById[$headerId]['fieldType'];
			}
			if (!is_string($fieldName) || strlen($fieldName) <= 0)
			{
				continue;
			}

			if (isset($row[$this->headerIndex[$headerId]]))
			{
				$value = $row[$this->headerIndex[$headerId]];

				if ($fieldType === 'integer')
				{
					$value = (int)$value;
				}
				else if ($fieldType === 'boolean')
				{
					$value = ToUpper($value);
					$yesStr = ToUpper(Loc::getMessage('MAIN_YES'));
					$value = ($value === 'Y' || $value === $yesStr || $value == 1) ? 'Y' : 'N';
				}

				$bankDetailFields[$fieldName] = $value;
			}
		}

		if (!empty($bankDetailFields))
			$bankDetailFields['COUNTRY_ID'] = $countryId;

		$result->setData($bankDetailFields);

		return $result;
	}

	protected function parseRequisiteKey($row)
	{
		$result = new Main\Result();

		$keyFieldsPresent = false;
		foreach ($this->requisiteKeyFields as $fieldName)
		{
			if (isset($this->headerGroupCountryIdMap['requisite'][0][$this->rqFieldPrefix.$fieldName]))
			{
				$keyFieldsPresent = true;
				break;
			}
		}
		if (!$keyFieldsPresent)
		{
			$result->addError(new Main\Error(
					Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_RQ_KEY_FIELDS_NOT_PRESENT'),
					self::ERR_RQ_KEY_FIELDS_NOT_PRESENT)
			);
			return $result;
		}

		$keyValue = '';
		if (is_array($row))
		{
			foreach ($this->requisiteKeyFields as $fieldName)
			{
				$fieldName = $this->rqFieldPrefix.$fieldName;
				if (isset($this->headerIndex[$fieldName]))
				{
					$index = $this->headerIndex[$fieldName];
					if (isset($row[$index]))
						$keyValue .= strval($row[$index]);
				}
			}
		}
		$result->setData(array($keyValue));

		return $result;
	}

	protected function parseBankDetailKey($row)
	{
		$result = new Main\Result();

		$keyFieldsPresent = false;
		foreach ($this->bankDetailKeyFields as $fieldName)
		{
			if (isset($this->headerGroupCountryIdMap['bankDetail'][0][$this->bdFieldPrefix.$fieldName]))
			{
				$keyFieldsPresent = true;
				break;
			}
		}
		if (!$keyFieldsPresent)
		{
			$result->addError(new Main\Error(
					Loc::getMessage('CRM_RQ_IMP_HLPR_ERR_BD_KEY_FIELDS_NOT_PRESENT'),
					self::ERR_BD_KEY_FIELDS_NOT_PRESENT)
			);
			return $result;
		}

		$keyValue = '';
		if (is_array($row))
		{
			foreach ($this->bankDetailKeyFields as $fieldName)
			{
				$fieldName = $this->bdFieldPrefix.$fieldName;
				if (isset($this->headerIndex[$fieldName]))
				{
					$index = $this->headerIndex[$fieldName];
					if (isset($row[$index]))
						$keyValue .= strval($row[$index]);
				}
			}
		}
		$result->setData(array($keyValue));

		return $result;
	}

	protected function makeErrorWithRowNumber(Main\Error $error, $rowNumber)
	{
		$errMsg = $error->getMessage();
		$rowNumberText = Main\Localization\Loc::getMessage(
			'CRM_RQ_IMP_HLPR_ERR_ROW_NUMBER_TEXT',
			array('#ROW_NUMBER#' => $rowNumber)
		);

		$result = new Main\Error($errMsg.' '.$rowNumberText, $error->getCode());

		return $result;
	}
}
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Crm\EntityPreset;
use \Bitrix\Crm\EntityRequisite;
use \Bitrix\Crm\EntityBankDetail;
use \Bitrix\Crm\EntityAddress;
use \Bitrix\Crm\EntityAddressType;

class CBPCrmGetRequisitesInfoActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'CrmEntityType' => null,
			'CrmEntityId' => null,
			'AddressTypeId' => null,
			'RequisitePresetId' => null,

			// return
			'RequisitePresetFields' => null,
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$this->CrmEntityType, $this->CrmEntityId] = $this->defineCrmEntityWithRequisites();
		$executionStatus = $this->assertProperties();
		if ($executionStatus !== CBPActivityExecutionStatus::Executing)
		{
			return $executionStatus;
		}

		$presetFieldIds = self::getPresetsFieldNames()[$this->RequisitePresetId];
		$requisiteSettings = EntityRequisite::getSingleInstance()->loadSettings(
			$this->CrmEntityType,
			$this->CrmEntityId
		);
		$requisite = $this->getPresetRequisiteFieldsValues($presetFieldIds, $requisiteSettings);
		$bankDetail = $this->getPresetBankDetailFieldsValues($requisiteSettings, $requisite);

		$this->arProperties = array_merge($this->arProperties, $requisite, $bankDetail);

		if (
			CBPHelper::isEmptyValue($this->AddressTypeId) === false
			&& EntityAddressType::isDefined($this->AddressTypeId)
			&& in_array('RQ_ADDR', $presetFieldIds)
		)
		{
			$addresses = EntityRequisite::getAddresses($requisite['ID']);
			if (array_key_exists($this->AddressTypeId, $addresses))
			{
				$this->arProperties = array_merge($this->arProperties, $addresses[$this->AddressTypeId]);
			}
		}

		return CBPActivityExecutionStatus::Closed;
	}

	protected function defineCrmEntityWithRequisites(): array
	{
		[$entityType, $entityId] = explode('_', $this->GetDocumentId()[2]);

		$entityTypeId = CCrmOwnerType::ResolveID($entityType);
		$entityId = intval($entityId);

		if ($entityTypeId === CCrmOwnerType::Lead || $entityTypeId === CCrmOwnerType::Deal)
		{
			$entityClass = 'CCrm' . ucfirst(strtolower($entityType));
			$entity = call_user_func_array([$entityClass, 'GetById'], [$entityId, false]);

			$entityTypeId = intval($this->CrmEntityType);
			$entityId = intval($entity[CCrmOwnerType::ResolveName($this->CrmEntityType) . '_ID']);
		}
		elseif ($entityTypeId !== CCrmOwnerType::Company && $entityTypeId !== CCrmOwnerType::Contact)
		{
			$entityTypeId = $entityId = 0;
		}

		return [$entityTypeId, $entityId];
	}

	protected function assertProperties(): int
	{
		if (EntityRequisite::checkEntityType($this->CrmEntityType) === false)
		{
			$this->WriteToTrackingService(GetMessage('CRM_GRI_ENTITY_TYPE_ERROR'));
			return CBPActivityExecutionStatus::Closed;
		}
		if ($this->CrmEntityId <= 0)
		{
			$entityName = CCrmOwnerType::ResolveName($this->CrmEntityType);
			$this->WriteToTrackingService(GetMessage("CRM_GRI_{$entityName}_NOT_EXISTS"));
			return CBPActivityExecutionStatus::Closed;
		}
		if (self::isRequisitePresetExists($this->CrmEntityType, $this->CrmEntityId, $this->RequisitePresetId) === false)
		{
			$presetName = self::getPropertiesDialogMap()['RequisitePresetId']['Options'][$this->RequisitePresetId];
			$this->WriteToTrackingService(GetMessage(
				'CRM_GRI_REQUISITE_PRESET_NOT_EXIST',
				array(
					'#TEMPLATE#' => $presetName,
					'#TYPE#' => GetMessage('CRM_GRI_ENTITY_' . CCrmOwnerType::ResolveName($this->CrmEntityType)),
					'#ID#' => $this->CrmEntityId
				)
			));
			return CBPActivityExecutionStatus::Closed;
		}
		return CBPActivityExecutionStatus::Executing;
	}

	protected function getPresetBankDetailFieldsValues(array $requisiteSettings, array $requisite): array
	{
		return $this->getBankDetailBySettings(
			$requisiteSettings,
			$requisite,
			EntityBankDetail::getSingleInstance()->getRqFields()
		);
	}

	protected function getBankDetailBySettings(array $requisiteSettings, array $requisite, array $bankDetailFieldNames)
	{
		$dbBankDetails = EntityBankDetail::getSingleInstance()->getList(array(
			'select' => $bankDetailFieldNames,
			'filter' => [
				'=ENTITY_ID' => $requisite['ID'],
				'=ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
				'=COUNTRY_ID' => EntityPreset::getSingleInstance()->getById($requisite['PRESET_ID'])['COUNTRY_ID']
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			]
		));

		if (array_key_exists('BANK_DETAIL_ID_SELECTED', $requisiteSettings))
		{
			return $this->getSelectedByColumnId(
				$dbBankDetails->fetchAll(),
				$requisiteSettings['BANK_DETAIL_ID_SELECTED']
			);
		}
		else
		{
			return $dbBankDetails->fetch() ?: [];
		}
	}

	protected function getSelectedByColumnId(array $data, int $selectedId): array
	{
		if (count($data) === 0)
		{
			return array();
		}
		elseif (count($data) === 1 || $selectedId === 0)
		{
			return $data[0];
		}

		$selectedData = array();
		foreach ($data as $value)
		{
			if ($value['ID'] == $selectedId)
			{
				$selectedData = $value;
				break;
			}
		}
		return $selectedData;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $workflowTemplate, $workflowParameters, $workflowVariables, $currentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule('crm'))
		{
			return '';
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $workflowTemplate,
			'workflowParameters' => $workflowParameters,
			'workflowVariables' => $workflowVariables,
			'currentValues' => $currentValues,
			'formName' => $formName,
			'siteId' => $siteId
		]);

		$map = self::getPropertiesDialogMap();
		if ($documentType[2] === CCrmOwnerType::ContactName || $documentType[2] === CCrmOwnerType::CompanyName)
		{
			unset($map['CrmEntityType']);
		}
		$dialog->setMap($map);

		$dialog->setRuntimeData(array(
			'PresetsInfo' => array_column(EntityPreset::getListForRequisiteEntityEditor(), 'COUNTRY_ID', 'ID')
		));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$workflowTemplate, &$workflowParameters, &$workflowVariables, $currentValues, &$errors)
	{
		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$documentService = $runtime->GetService('DocumentService');

		$properties = self::getValues(
			$documentType,
			$documentService,
			self::getPropertiesDialogMap(),
			$currentValues,
			$errors
		);

		$errors = self::ValidateProperties(
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);
		if (count($errors) > 0)
		{
			return false;
		}

		$properties['RequisitePresetFields'] = self::getReturnValuesMap($currentValues);

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $properties;

		return true;
	}

	protected static function getReturnValuesMap(array $currentValues)
	{
		$map = self::getPropertiesDialogMap();
		$fieldPresetId = $map['RequisitePresetId']['FieldName'];
		$fieldAddressTypeId = $map['AddressTypeId']['FieldName'];

		$requisiteFieldsMap = self::getRequisiteFieldsMap();
		$userFieldsMap = self::getUserFieldsMap();

		if (self::isValueVariable($map['RequisitePresetId'], $currentValues[$fieldPresetId]) === false)
		{
			$presetId = $currentValues[$fieldPresetId];
			$addressTypeId = $currentValues[$fieldAddressTypeId];
			$returnValuesMap = array();

			foreach (self::getPresetsFieldNames()[$presetId] as $fieldName)
			{
				if (array_key_exists($fieldName, $requisiteFieldsMap))
				{
					$returnValuesMap[$fieldName] = $requisiteFieldsMap[$fieldName];
				}
				elseif (array_key_exists($fieldName, $userFieldsMap))
				{
					$returnValuesMap[$fieldName] = $userFieldsMap[$fieldName];
				}
				elseif ($fieldName === EntityRequisite::ADDRESS)
				{
					$returnValuesMap = array_merge($returnValuesMap, self::getAddressFieldsMap());
				}
			}
		}
		else
		{
			$returnValuesMap = array_merge($requisiteFieldsMap, $userFieldsMap);
		}

		return array_merge($returnValuesMap, self::getBankDetailMap());
	}

	protected static function isValueVariable(array $fieldProperties, $fieldValue): bool
	{
		if ($fieldProperties['Type'] === \Bitrix\Bizproc\FieldType::SELECT)
		{
			return !array_key_exists($fieldValue, $fieldProperties['Options']);
		}
		return CBPActivity::isExpression($fieldValue);
	}

	protected function getPresetRequisiteFieldsValues(array $presetFieldsIds, array $requisiteSettings): array
	{
		$requisite = $this->getRequisiteBySettings(
			$requisiteSettings,
			array_merge($presetFieldsIds, ['ID', 'PRESET_ID'])
		);

		unset($requisite['RQ_ADDR']);
		foreach (self::getUserFieldsMap() as $fieldKey => $fieldProperties)
		{
			if (array_key_exists($fieldKey, $requisite) && $fieldProperties['Type'] === \Bitrix\Bizproc\FieldType::BOOL)
			{
				$requisite[$fieldKey] = CBPHelper::getBool($requisite[$fieldKey]) ? 'Y' : 'N';
			}
		}
		return $requisite;
	}

	protected function getRequisiteBySettings(array $requisiteSettings, array $requisiteFieldNames): array
	{
		$dbRequisites = EntityRequisite::getSingleInstance()->getList(array(
			'select' => $requisiteFieldNames,
			'filter' => [
				'=ENTITY_TYPE_ID' => $this->CrmEntityType,
				'=ENTITY_ID' => $this->CrmEntityId,
				'=PRESET_ID' => $this->RequisitePresetId
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			]
		));
		if (array_key_exists('REQUISITE_ID_SELECTED', $requisiteSettings))
		{
			return $this->getSelectedByColumnId(
				$dbRequisites->fetchAll(),
				$requisiteSettings['REQUISITE_ID_SELECTED']
			);
		}
		else
		{
			return $dbRequisites->fetch();
		}
	}

	protected static function getPresetsFieldNames(): array
	{
		static $presetFieldNames = null;
		if(isset($presetFieldNames))
		{
			return $presetFieldNames;
		}

		$presets = EntityPreset::getSingleInstance()->getList([
			'select' => ['ID', 'SETTINGS']
		])->fetchAll();
		foreach ($presets as $preset)
		{
			$presetFieldNames[$preset['ID']] = array_column($preset['SETTINGS']['FIELDS'] ?: [], 'FIELD_NAME');
		}
		return $presetFieldNames;
	}

	protected static function getValues(array $documentType, CBPDocumentService $documentService, array $fieldsMap, array $currentValues, array &$errors): array
	{
		$values = array();

		foreach ($fieldsMap as $propertyKey => $fieldProperties)
		{
			$field = $documentService->getFieldTypeObject($documentType, $fieldProperties);
			if (!$field)
			{
				continue;
			}

			$values[$propertyKey] = $field->extractValue(
				['Field' => $fieldProperties['FieldName']],
				$currentValues,
				$errors
			);

			if(is_null($values[$propertyKey]) && array_key_exists('Getter', $fieldProperties))
			{
				$values[$propertyKey] = $fieldProperties['Getter'](
					new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, ['documentType' => $documentType]),
					$fieldProperties,
					['Properties' => $currentValues],
					false
				);
			}
		}
		return $values;
	}

	public static function ValidateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		foreach (self::GetPropertiesDialogMap() as $propertyKey => $fieldProperties)
		{
			if (CBPHelper::getBool($fieldProperties['Required']) &&
				CBPHelper::isEmptyValue($testProperties[$propertyKey]))
			{
				$errors[] = [
					'code' => 'NotExist',
					'parameter' => 'FieldValue',
					'message' => GetMessage("CRM_GRI_EMPTY_PROP", ['#PROPERTY#' => $fieldProperties['Name']])
				];
			}
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
	}

	protected static function getRequisiteFieldsMap(): array
	{
		$rqFields = EntityRequisite::getSingleInstance()->getRqFields();
		$addressFieldIndex = array_search(EntityRequisite::ADDRESS, $rqFields, true);
		if ($addressFieldIndex !== false)
		{
			unset($rqFields[$addressFieldIndex]);
		}

		return self::combineFieldsMap(
			$rqFields,
			array_merge(
				EntityRequisite::getSingleInstance()->getFieldsTitles(GetCountryIdByCode('US')),
				EntityRequisite::getSingleInstance()->getFieldsTitles()
			)
		);
	}

	protected static function getUserFieldsMap(): array
	{
		static $userFieldsMap = null;
		if(is_array($userFieldsMap))
		{
			return $userFieldsMap;
		}

		$userFieldNames = array_filter(
			EntityPreset::getSingleInstance()->getSettingsFieldsOfPresets(EntityPreset::Requisite),
			[self::class, 'isUserField']
		);
		$userFieldIds = \Bitrix\Main\UserFieldTable::getList(
			[
				'select' => ['ID'],
				'filter' => [
					'ENTITY_ID' => EntityRequisite::getSingleInstance()->getUfId(),
					'?FIELD_NAME' => implode(' | ', $userFieldNames),
				]
			]
		)->fetchAll();
		$userFieldTitles = EntityRequisite::getSingleInstance()->getUserFieldsTitles();

		$userFieldsMap = [];
		foreach ($userFieldIds as $id)
		{
			$field = \Bitrix\Main\UserFieldTable::getFieldData($id['ID']);
			$fieldName = $field['FIELD_NAME'];

			$name = $userFieldTitles[$fieldName];

			$userFieldsMap[$fieldName] = [
				'Name' => $name,
				'FieldName' => $fieldName,
				'Type' => $field['USER_TYPE_ID'] === 'boolean' ? 'bool' : $field['USER_TYPE_ID'],
			];
		}

		return $userFieldsMap;
	}

	protected static function isUserField($fieldId): bool
	{
		return substr($fieldId, 0, strlen('UF_')) === 'UF_';
	}

	protected static function getBankDetailMap(): array
	{
		return self::combineFieldsMap(
			EntityBankDetail::getSingleInstance()->getRqFields(),
			array_merge(
				EntityBankDetail::getSingleInstance()->getFieldsTitles(GetCountryIdByCode('US')),
				EntityBankDetail::getSingleInstance()->getFieldsTitles()
			)
		);
	}

	protected static function combineFieldsMap(array $fieldNames, array $fieldTitles, string $fieldType = \Bitrix\Bizproc\FieldType::STRING): array
	{
		$fieldsMap = array();

		foreach ($fieldNames as $name)
		{
			if(CBPHelper::isEmptyValue($fieldTitles[$name]) === false)
			{
				$fieldsMap[$name] = [
					'Name' => $fieldTitles[$name],
					'FieldName' => $name,
					'Type' => $fieldType
				];
			}
		}

		return $fieldsMap;
	}

	protected static function getAddressFieldsMap(): array
	{
		$addressFields = array();
		$addressFieldsInfo = EntityAddress::getFieldsInfo();

		$defaultFieldType = \Bitrix\Bizproc\FieldType::STRING;

		foreach (EntityAddress::getLabels() as $fieldId => $fieldName)
		{
			if($fieldId !== "ADDRESS")
			{
				$fieldType = $addressFieldsInfo[$fieldId]['TYPE'];
				$addressFields[$fieldId] = [
					'Name' => $fieldName,
					'FieldName' => $fieldId,
					'Type' => is_null($fieldType) ? $defaultFieldType : self::resolveAddressField($fieldType)
				];
			}
		}

		return $addressFields;
	}

	protected static function resolveAddressField(string $type): ?string
	{
		switch ($type)
		{
			case 'integer':
				$bpType = \Bitrix\Bizproc\FieldType::INT;
				break;
			case 'boolean':
				$bpType = \Bitrix\Bizproc\FieldType::BOOL;
				break;
			default:
				$bpType = \Bitrix\Bizproc\FieldType::STRING;
				break;
		}
		return $bpType;
	}

	protected static function isEntityExists(int $crmEntityType, int $crmEntityId): bool
	{
		if ($crmEntityType === CCrmOwnerType::Contact)
		{
			return CCrmContact::Exists($crmEntityId);
		}
		if ($crmEntityType === CCrmOwnerType::Company)
		{
			return CCrmCompany::Exists($crmEntityId);
		}

		return false;
	}

	protected static function isRequisitePresetExists(int $crmEntityType, int $crmEntityId, int $requisitePresetId): bool
	{
		$presetsIds = EntityRequisite::getPresetsByEntities($crmEntityType, [$crmEntityId]);

		return in_array($requisitePresetId, $presetsIds, true);
	}

	protected static function getPropertiesDialogMap(): array
	{
		static $presetsInfo = null;
		if (is_null($presetsInfo))
		{
			$presetsInfo = EntityPreset::getListForRequisiteEntityEditor();
		}

		return [
			'CrmEntityType' => [
				'Name' => GetMessage('CRM_GRI_ENTITY_TYPE'),
				'FieldName' => 'crm_entity_type',
				'Type' => \Bitrix\Bizproc\FieldType::SELECT,
				'Required' => true,
				'Options' => [
					CCrmOwnerType::Company => GetMessage('CRM_GRI_ENTITY_COMPANY'),
					CCrmOwnerType::Contact => GetMessage('CRM_GRI_ENTITY_CONTACT')
				],
				'Getter' => function($dialog, $property, $currentActivity, $compatible)
				{
					$documentType = $dialog->getDocumentType()[2];
					if($documentType === CCrmOwnerType::ContactName || $documentType === CCrmOwnerType::CompanyName)
					{
						return CCrmOwnerType::ContactName;
					}
					return $currentActivity['Properties']['CrmEntityType'];
				}
			],
			'RequisitePresetId' => [
				'Name' => GetMessage('CRM_GRI_REQUISITE_TEMPLATES'),
				"FieldName" => 'requisite_preset',
				'Type' => \Bitrix\Bizproc\FieldType::SELECT,
				'Required' => true,
				'Options' => array_column($presetsInfo, 'NAME', 'ID'),
			],
			'AddressTypeId' => [
				'Name' => GetMessage('CRM_GRI_ADDRESS_TYPE'),
				'FieldName' => 'address_type_id',
				'Type' => \Bitrix\Bizproc\FieldType::SELECT,
				'Options' => array_filter(
					EntityAddressType::getAllDescriptions(),
					function($value) {
						return !CBPHelper::isEmptyValue($value);
					}
				),
			],
			'CountryId' => [
				'Name' => GetMessage('CRM_GRI_COUNTRY'),
				'FieldName' => 'country_id',
				'Type' => \Bitrix\Bizproc\FieldType::SELECT,
				'Options' => self::getCountryNamesByPresets($presetsInfo),
			]
		];
	}

	protected static function getCountryNamesByPresets(array $presets): array
	{
		$countryNames = array();
		foreach ($presets as $presetMap)
		{
			$countryNames[$presetMap['COUNTRY_ID']] = GetCountryByID($presetMap['COUNTRY_ID']);
		}
		return $countryNames;
	}
}

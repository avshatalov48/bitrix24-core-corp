<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use \Bitrix\Crm\EntityPreset;
use \Bitrix\Crm\EntityRequisite;
use \Bitrix\Crm\EntityBankDetail;

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CrmGetRequisitesInfoActivity');

class CBPCrmChangeRequisiteActivity extends CBPCrmGetRequisitesInfoActivity
{
	public function __construct($name)
	{
		parent::__construct($name);

		$this->arProperties['FieldsValues'] = null;
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$this->CrmEntityType, $this->CrmEntityId] = $this->defineCrmEntityWithRequisites();
		$this->logRequisiteProperties();

		$executionStatus = $this->assertProperties();
		if ($executionStatus !== CBPActivityExecutionStatus::Executing)
		{
			return $executionStatus;
		}

		$fieldsValues = self::normalizeFieldsValues($this->FieldsValues);
		$fieldsValues['RequisiteFields'] = $this->filterPresetRequisiteFields($fieldsValues['RequisiteFields']);

		$this->logRequisiteValues($fieldsValues);

		$requisiteSettings = EntityRequisite::getSingleInstance()->loadSettings(
			$this->CrmEntityType,
			$this->CrmEntityId
		);

		$requisiteId = $this->getRequisiteId($requisiteSettings);
		$bankDetailId = $this->getBankDetailId($requisiteSettings, $requisiteId, $this->RequisitePresetId);

		$this->updateRequisite($requisiteId, $bankDetailId, $fieldsValues);

		return CBPActivityExecutionStatus::Closed;
	}

	protected function logRequisiteValues(array $requisiteValues): void
	{
		$requisiteFieldValues = $requisiteValues['RequisiteFields'];
		$addressFieldValues = $requisiteValues['RequisiteFields'][EntityRequisite::ADDRESS][$this->AddressTypeId] ?? [];
		unset($requisiteFieldValues['RequisiteFields'][EntityRequisite::ADDRESS]);
		$countryId = EntityRequisite::getSingleInstance()->getCountryIdByPresetId($this->RequisitePresetId);

		$debugValues = array_merge($requisiteFieldValues, $addressFieldValues, $requisiteValues['BankDetailFields']);

		$map = array_merge(
			static::getRequisiteFieldsMap($countryId),
			static::getUserFieldsMap(),
			static::getAddressFieldsMap(),
			static::getBankDetailMap(),
		);

		$this->writeDebugInfo($this->getDebugInfo($debugValues, array_intersect_key($map, $debugValues)));
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return parent::getPropertiesDialogMap();
	}

	protected function filterPresetRequisiteFields(array $requisiteFieldsValues)
	{
		$requisiteFieldsIds = EntityRequisite::getSingleInstance()->getRqFields();
		$presetFieldsIds = array_column(
			EntityPreset::getSingleInstance()->getById($this->RequisitePresetId)['SETTINGS']['FIELDS'],
			null,
			'FIELD_NAME'
		);

		foreach ($requisiteFieldsIds as $fieldId)
		{
			if (isset($requisiteFieldsValues[$fieldId]) && array_key_exists($fieldId, $presetFieldsIds) === false)
			{
				unset($requisiteFieldsValues[$fieldId]);
			}
		}

		return $requisiteFieldsValues;
	}

	protected function getRequisiteId(array $requisiteSettings): int
	{
		return (int)$this->getRequisiteBySettings($requisiteSettings, ['ID'])['ID'];
	}

	protected function getBankDetailId(array $requisiteSettings, int $requisiteId, int $presetId): int
	{
		return (int)$this->getBankDetailBySettings(
			$requisiteSettings,
			['ID' => $requisiteId, 'PRESET_ID' => $presetId],
			['ID']
		)['ID'];
	}

	protected function updateRequisite(int $requisiteId, int $bankDetailId, array $fieldsValues)
	{
		$requisite = EntityRequisite::getSingleInstance();
		$bankDetail = EntityBankDetail::getSingleInstance();

		$fieldsValues = $this->prepareRequisiteFieldValues($requisiteId, $fieldsValues);

		if ($fieldsValues['RequisiteFields'])
		{
		 	$res = $requisite->checkBeforeUpdate($requisiteId, $fieldsValues['RequisiteFields']);
		 	if ($res->isSuccess())
			{
				$requisite->update($requisiteId, $fieldsValues['RequisiteFields']);
			}
		 	else
			{
				$errorMessages = $res->getErrorMessages();
				$this->WriteToTrackingService(end($errorMessages), 0, CBPTrackingType::Error);
			}
		}
		if ($fieldsValues['BankDetailFields'])
		{
			$res = $bankDetail->checkBeforeUpdate($bankDetailId, $fieldsValues['BankDetailFields']);
			if ($res->isSuccess())
			{
				$bankDetail->update($bankDetailId, $fieldsValues['BankDetailFields']);
			}
			else
			{
				$errorMessages = $res->getErrorMessages();
				$this->WriteToTrackingService(end($errorMessages), 0, CBPTrackingType::Error);
			}
		}
	}

	protected function prepareRequisiteFieldValues(int $requisiteId, array $fieldsValues): array
	{
		$addressFields = EntityRequisite::ADDRESS;
		$addressTypeId = $this->AddressTypeId;

		foreach ($fieldsValues as $requisiteSectionName => $requisiteSectionFields)
		{
			foreach ($requisiteSectionFields as $fieldId => $value)
			{
				$fieldsValues[$requisiteSectionName][$fieldId] = $this->parseValue($value);
			}
		}

		$fieldsValues['RequisiteFields'] = $this->prepareUserFieldValues($fieldsValues['RequisiteFields']);

		if (array_key_exists(EntityRequisite::ADDRESS, $fieldsValues['RequisiteFields']))
		{
			$fieldsValues['RequisiteFields'][$addressFields][$addressTypeId] = $this->prepareAddressFieldValues(
				$requisiteId,
				$fieldsValues['RequisiteFields'][$addressFields][$addressTypeId]
			);
		}

		return $fieldsValues;
	}

	protected function prepareUserFieldValues($requisiteFieldValues)
	{
		foreach (self::getUserFieldsMap() as $fieldId => $fieldProperties)
		{
			if ($fieldProperties['Type'] === 'bool' && array_key_exists($fieldId, $requisiteFieldValues))
			{
				$requisiteFieldValues[$fieldId] = CBPHelper::getBool($requisiteFieldValues[$fieldId]);
			}
		}

		return $requisiteFieldValues;
	}

	protected function prepareAddressFieldValues(int $requisiteId, array $rawAddressFields) : array
	{
		$addressFields = EntityRequisite::getAddresses($requisiteId)[$this->AddressTypeId] ?: [];
		$addressFields = array_merge($addressFields, $rawAddressFields);

		if (\Bitrix\Crm\EntityAddress::isLocationModuleIncluded() && isset($addressFields['LOC_ADDR_ID']))
		{
			$locationAddress = \Bitrix\Crm\EntityAddress::makeLocationAddressByFields($addressFields);

			$addressFields = isset($locationAddress) ? ['LOC_ADDR' => $locationAddress] : [];
		}

		return $addressFields;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $workflowTemplate, $workflowParameters, $workflowVariables, $currentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		$dialog = parent::GetPropertiesDialog(...func_get_args());

		$currentPresetId = (int)$dialog->getCurrentValue('requisite_preset');
		$presetCountryId = 0;
		if ($currentPresetId !== 0)
		{
			$presetCountryId = EntityRequisite::getSingleInstance()->getCountryIdByPresetId($currentPresetId);
		}

		$runtimeData = [
			'PathToParentClassDir' => pathinfo(realpath($dialog->getActivityFile()), PATHINFO_DIRNAME),
			'RequisiteFieldsMap' => array_merge(
				self::getRequisiteFieldsMap($presetCountryId),
				self::getUserFieldsMap()
			),
			'BankDetailFieldsMap' => self::getBankDetailMap(),
			'AddressFieldsMap' => self::getAddressFieldsMap(),
			'PresetRequisiteFieldNames' => self::getPresetsFieldNames()
		];

		$dialog
			->setRuntimeData(array_merge($dialog->getRuntimeData(), $runtimeData))
			->setActivityFile(__FILE__)
			->setCurrentValues(self::mergeAddressValuesWithRqValues($dialog->getCurrentValues()))
		;

		return $dialog;
	}

	protected static function mergeAddressValuesWithRqValues(array $currentValues): array
	{
		$fieldsValues = $currentValues['FieldsValues'];
		$addressFields = EntityRequisite::ADDRESS;

		if (
			isset($fieldsValues)
			&& array_key_exists($addressFields, $fieldsValues)
			&& reset($fieldsValues[$addressFields])
			&& !CBPHelper::isEmptyValue(current($fieldsValues[$addressFields]))
		)
		{
			$fieldsValues = array_merge($fieldsValues, current($fieldsValues[$addressFields]));
		}

		unset($fieldsValues[$addressFields]);
		$currentValues['FieldsValues'] = $fieldsValues;

		return $currentValues;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$workflowTemplate, &$workflowParameters, &$workflowVariables, $currentValues, &$errors)
	{
		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$documentService = $runtime->GetService('DocumentService');

		$properties = self::getValues(
			$documentType, $documentService, parent::getPropertiesDialogMap(),
			$currentValues, $errors
		);

		$properties['FieldsValues'] = [
			'RequisiteFields' => array_merge(
				self::getValues(
					$documentType, $documentService, array_intersect_key(self::getRequisiteFieldsMap(), $currentValues),
					$currentValues, $errors
				),
				self::getValues(
					$documentType, $documentService, array_intersect_key(self::getUserFieldsMap(), $currentValues),
					$currentValues, $errors
				)
			),
			'BankDetailFields' => self::getValues(
				$documentType, $documentService, array_intersect_key(self::getBankDetailMap(), $currentValues),
				$currentValues, $errors
			),
		];

		if (isset($properties['AddressTypeId']))
		{
			$properties['FieldsValues']['RequisiteFields'][EntityRequisite::ADDRESS] = [
				$properties['AddressTypeId'] => self::getValues(
					$documentType, $documentService, array_intersect_key(self::getAddressFieldsMap(), $currentValues),
					$currentValues, $errors
				)
			];
		}

		$errors = self::ValidateProperties(
			$properties,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);

		$isCorrect = (count($errors) <= 0);
		if($isCorrect)
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
			$arCurrentActivity["Properties"] = $properties;
		}

		return $isCorrect;
	}

	protected static function getRequisiteFieldsMap(int $countryId = 0): array
	{
		$requisiteFieldsMap = parent::getRequisiteFieldsMap($countryId);
		// the RQ_NAME field is formed from other fields. it's immutable
		unset($requisiteFieldsMap['RQ_NAME']);

		return $requisiteFieldsMap;
	}

	protected static function getAddressFieldsMap(): array
	{
		$addressFieldsMap = parent::getAddressFieldsMap();
		// the LOC_ADDR_ID field is immutable
		unset($addressFieldsMap['LOC_ADDR_ID']);

		return $addressFieldsMap;
	}

	public static function ValidateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$testProperties['FieldsValues'] = self::normalizeFieldsValues($testProperties['FieldsValues'] ?? []);
		$errors = parent::ValidateProperties($testProperties, $user);

		$isPropertySpecified = false;
		foreach ($testProperties['FieldsValues'] as $requisiteTypeFields)
		{
			foreach ($requisiteTypeFields as $fieldType => $fieldsValues)
			{
				if ($fieldType === EntityRequisite::ADDRESS)
				{
					$fieldsValues = reset($fieldsValues);
				}
				if (!CBPHelper::isEmptyValue($fieldsValues)) {
					$isPropertySpecified = true;
					break;
				}
			}
		}

		if($isPropertySpecified === false)
		{
			$errors[] = [
				'code' => 'NotExist',
				'parameter' => 'FieldValue',
				'message' => GetMessage('CRM_CRA_EMPTY_FIELDS')
			];
		}

		return $errors;
	}

	protected static function normalizeFieldsValues(array $fieldsValues): array
	{
		if (!is_array($fieldsValues['RequisiteFields']))
		{
			$fieldsValues['RequisiteFields'] = [];
		}
		if (!is_array($fieldsValues['BankDetailFields']))
		{
			$fieldsValues['BankDetailFields'] = [];
		}
		if (array_key_exists(EntityRequisite::ADDRESS, $fieldsValues['RequisiteFields']))
		{
			foreach ($fieldsValues['RequisiteFields'][EntityRequisite::ADDRESS] as $addressTypeId => &$addressFields)
			{
				if (!is_array($addressFields))
				{
					$addressFields = [];
				}
			}
		}

		return $fieldsValues;
	}

	protected static function getPropertiesDialogMap(): array
	{
		return array_merge(parent::getPropertiesDialogMap(), [
			'FieldsValues' => [
				'FieldName' => 'FieldsValues',
				'Getter' => function ($dialog, $property, $currentActivity, $compatible)
				{
					$requisiteFields = $currentActivity['Properties']['FieldsValues']['RequisiteFields'];
					$bankDetailFields = $currentActivity['Properties']['FieldsValues']['BankDetailFields'];

					return array_merge(
						is_array($requisiteFields) ? $requisiteFields : [],
						is_array($bankDetailFields) ? $bankDetailFields : []
					);
				}
			]
		]);
	}
}

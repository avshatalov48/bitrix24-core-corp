<?php

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Crm\Integration\BizProc\Document;
use Bitrix\Crm;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CBPCrmCreateDynamicActivity extends \Bitrix\Bizproc\Activity\BaseActivity
{
	protected static $requiredModules = ['crm'];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'DynamicTypeId' => 0,
			'DynamicEntitiesFields' => [],

			// return
			'ItemId' => null,
		];

		$this->SetPropertiesTypes([
			'DynamicTypeId' => ['Type' => FieldType::INT],
		]);
	}

	protected function reInitialize()
	{
		parent::reInitialize();
		$this->ItemId = 0;
	}

	protected function prepareProperties(): void
	{
		parent::prepareProperties();

		$entityFieldsValues = [];
		$fieldIdPrefixLength = mb_strlen($this->DynamicTypeId . '_');
		foreach ($this->DynamicEntitiesFields as $fieldId => $fieldValue)
		{
			$realFieldId = mb_substr($fieldId, $fieldIdPrefixLength);
			$entityFieldsValues[$realFieldId] = $fieldValue;
		}
		$this->preparedProperties['DynamicEntitiesFields'] = $entityFieldsValues;
	}

	protected function checkProperties(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::checkProperties();

		$documentType = CCrmBizProcHelper::ResolveDocumentType($this->DynamicTypeId);
		if (!isset($documentType))
		{
			$errors->setError(new Error(Loc::getMessage('CRM_CDA_DYNAMIC_TYPE_ID_ERROR')));
		}

		return $errors;
	}

	protected function internalExecute(): \Bitrix\Main\ErrorCollection
	{
		$errorCollection = parent::internalExecute();

		$fieldsValues = [];
		foreach ($this->DynamicEntitiesFields as $fieldId => $fieldValue)
		{
			if (!CBPHelper::isEmptyValue($fieldValue))
			{
				$fieldsValues[$fieldId] = $fieldValue;
			}
		}

		$documentType = CCrmBizProcHelper::ResolveDocumentType($this->DynamicTypeId);
		try
		{
			$creationResult = static::getDocumentService()->CreateDocument($documentType, $fieldsValues);
		}
		catch (\Bitrix\Main\NotImplementedException $exception)
		{
			$creationResult = false;
		}

		if (is_string($creationResult))
		{
			$errorCollection->setError(new Error($creationResult));
		}
		elseif ($creationResult === false)
		{
			$errorCollection->setError(new Error(Loc::getMessage('CRM_CDA_ITEM_CREATION_ERROR')));
		}
		elseif (is_int($creationResult))
		{
			$this->ItemId = $creationResult;
			$this->preparedProperties['ItemId'] = $creationResult;
		}

		return $errorCollection;
	}

	protected static function getFileName(): string
	{
		return __FILE__;
	}

	protected static function extractPropertiesValues(PropertiesDialog $dialog, array $fieldsMap): Result
	{
		$result = parent::extractPropertiesValues($dialog, $fieldsMap);

		if ($result->isSuccess())
		{
			$currentValues = $result->getData();
			$entityTypeId = (int)$currentValues['DynamicTypeId'];

			$extractingFieldsResult = parent::extractPropertiesValues(
				$dialog,
				$fieldsMap['DynamicEntitiesFields']['Map'][$entityTypeId] ?? []
			);

			if ($extractingFieldsResult->isSuccess())
			{
				$currentValues['DynamicEntitiesFields'] = $extractingFieldsResult->getData();

				$result->setData($currentValues);
			}
			else
			{
				$result->addErrors($extractingFieldsResult->getErrors());
			}
		}

		return $result;
	}

	public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
	{
		$typesMap = Crm\Service\Container::getInstance()->getTypesMap();

		$typeNames = [];
		$entitiesFields = [];
		foreach ($typesMap->getFactories() as $factory)
		{
			$entityTypeId = $factory->getEntityTypeId();
			$documentType = CCrmBizProcHelper::ResolveDocumentType($entityTypeId);

			if (isset($documentType) && static::isTypeSupported($entityTypeId))
			{
				$typeNames[$entityTypeId] = static::getDocumentService()->getDocumentTypeName($documentType);
				$entitiesFields[$entityTypeId] = static::getEntityFields($entityTypeId);
			}
		}

		return [
			'DynamicTypeId' => [
				'Name' => Loc::getMessage('CRM_CDA_TYPE_ID'),
				'FieldName' => 'dynamic_type_id',
				'Type' => FieldType::SELECT,
				'Options' => $typeNames,
				'Required' => true,
			],
			'DynamicEntitiesFields' => [
				'FieldName' => 'dynamic_entities_fields',
				'Map' => $entitiesFields,
				'Getter' => function($dialog, $property, $currentActivity, $compatible) {
					return $currentActivity['Properties']['DynamicEntitiesFields'];
				},
			],
		];
	}

	private static function isTypeSupported(int $entityTypeId): bool
	{
		return Crm\Automation\Factory::isSupported($entityTypeId) && $entityTypeId !== CCrmOwnerType::Order;
	}

	private static function getEntityFields(int $entityTypeId): array
	{
		$documentType = CCrmBizProcHelper::ResolveDocumentType($entityTypeId);
		$documentService = static::getDocumentService();
		$entityFields = [];

		foreach ($documentService->GetDocumentFields($documentType) as $fieldId => $field)
		{
			$isIgnoredField = !$field['Editable'] || static::isInternalField($fieldId) || static::isMultiField($field);

			if (!$isIgnoredField || static::isRequiredFieldId($fieldId))
			{
				$entityFieldId = "{$entityTypeId}_{$fieldId}";

				$entityFields[$entityFieldId] = $field;
				$entityFields[$entityFieldId]['FieldName'] = $entityTypeId . '_' . mb_strtolower($fieldId);
			}
		}

		return $entityFields;
	}

	private static function isMultiField(array $field): bool
	{
		$fieldTypes = ['phone', 'email', 'web', 'im'];

		return in_array($field['Type'] ?? '', $fieldTypes, true);
	}

	protected static function isInternalField(string $fieldId): bool
	{
		$internalFieldIds = array_merge(
			Crm\UtmTable::getCodeList(),
			[
				Crm\Item::FIELD_NAME_ACCOUNT_CURRENCY_ID,
				Crm\Item::FIELD_NAME_OPPORTUNITY_ACCOUNT,
				Crm\Item::FIELD_NAME_TAX_VALUE_ACCOUNT,
				Crm\Item::FIELD_NAME_WEBFORM_ID,
			]
		);
		return in_array($fieldId, $internalFieldIds, true);
	}

	protected static function isRequiredFieldId(string $fieldId): bool
	{
		return $fieldId === Crm\Item::FIELD_NAME_CREATED_BY;
	}
}
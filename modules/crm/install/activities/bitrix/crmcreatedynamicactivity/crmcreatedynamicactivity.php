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
		];

		$this->SetPropertiesTypes([
			'DynamicTypeId' => ['Type' => FieldType::INT],
		]);
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

		if (!CCrmOwnerType::isPossibleDynamicTypeId($this->DynamicTypeId))
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

		$entityType = CCrmOwnerType::ResolveName($this->DynamicTypeId);
		$creationResult = Document\Dynamic::CreateDocument($entityType, $fieldsValues);
		if (is_string($creationResult))
		{
			$errorCollection->setError(new Error($creationResult));
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
		$typesMap = Crm\Service\Container::getInstance()->getDynamicTypesMap();
		$typesMap->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		$typeNames = [];
		$dynamicEntitiesFields = [];
		foreach ($typesMap->getTypes() as $typeId => $type)
		{
			$typeNames[$typeId] = $type->getTitle();

			$entityTypeId = $type->getEntityTypeId();
			$dynamicEntitiesFields[$entityTypeId] = [];
			foreach (Document\Dynamic::getEntityFields($entityTypeId) as $fieldId => $field)
			{
				if ($field['Editable'] && !static::isInternalField($fieldId) || static::isRequiredFieldId($fieldId))
				{
					$dynamicEntitiesFields[$entityTypeId]["{$entityTypeId}_{$fieldId}"] = [
						'Name' => $field['Name'],
						'FieldName' => $entityTypeId . '_' . mb_strtolower($fieldId),
						'Type' => $field['Type'],
						'Options' => $field['Options'] ?? null,
						'Default' => $field['Default'] ?? null,
						'Settings' => $field['Settings'] ?? null,
						'Multiple' => $field['Multiple'] ?? false,
					];
				}
			}
		}

		return [
			'DynamicTypeId' => [
				'Name' => Loc::getMessage('CRM_CDA_DYNAMIC_TYPE_ID'),
				'FieldName' => 'dynamic_type_id',
				'Type' => FieldType::SELECT,
				'Options' => $typeNames,
				'Required' => true,
			],
			'DynamicEntitiesFields' => [
				'FieldName' => 'dynamic_entities_fields',
				'Map' => $dynamicEntitiesFields,
				'Getter' => function($dialog, $property, $currentActivity, $compatible) {
					return $currentActivity['Properties']['DynamicEntitiesFields'];
				},
			],
		];
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
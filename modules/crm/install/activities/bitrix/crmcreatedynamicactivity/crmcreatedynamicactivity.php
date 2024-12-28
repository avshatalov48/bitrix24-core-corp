<?php

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Bizproc\Result\ResultDto;
use Bitrix\Crm;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @property-write int DynamicTypeId
 * @property-write array DynamicEntitiesFields
 * @property-write bool OnlyDynamicEntities
 * @property-write int|null ItemId
 * @property-write string|null ErrorMessage
 */
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
			'OnlyDynamicEntities' => 'N',

			// return
			'ItemId' => null,
			'ErrorMessage' => null,
		];

		$this->SetPropertiesTypes([
			'DynamicTypeId' => ['Type' => FieldType::INT],
			'ErrorMessage' => ['Type' => FieldType::STRING],
		]);
	}

	protected function reInitialize()
	{
		parent::reInitialize();
		$this->ItemId = 0;
		$this->ErrorMessage = null;
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

		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo());
		}
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

		$fieldsValues = $this->externalizeDocumentFields();
		$this->logDocumentFields($fieldsValues);

		$documentType = $this->getCreatedDocumentType();
		try
		{
			$creationResult = static::getDocumentService()->CreateDocument($documentType, $fieldsValues);
			if (CBPHelper::getBool($this->DynamicEntitiesFields['BindToCurrentElement'] ?? false))
			{
				$moduleId = $this->getDocumentType()[0] ?? '';
				if (is_int($creationResult) && $moduleId === 'crm')
				{
					[$currentEntityTypeId, $currentEntityId] = CCrmBizProcHelper::resolveEntityId($this->getDocumentId());
					$this->bindElements(
						new Crm\ItemIdentifier($currentEntityTypeId, $currentEntityId),
						new Crm\ItemIdentifier($this->DynamicTypeId, $creationResult),
					);
				}
			}
		}
		catch (\Bitrix\Main\NotImplementedException $exception)
		{
			$creationResult = false;
		}

		if (is_string($creationResult))
		{
			$errorCollection->setError(new Error($creationResult));
			$this->ErrorMessage = $creationResult;
		}
		elseif ($creationResult === false)
		{
			$errorCollection->setError(new Error(Loc::getMessage('CRM_CDA_ITEM_CREATION_ERROR')));
			$this->ErrorMessage = Loc::getMessage('CRM_CDA_ITEM_CREATION_ERROR');
		}
		elseif (is_int($creationResult))
		{
			$this->ItemId = $creationResult;
			$this->preparedProperties['ItemId'] = $creationResult;
		}

		if ($this->ItemId)
		{
			$this->fixResult($this->makeResultFromId($this->ItemId));
		}

		return $errorCollection;
	}

	protected function getCreatedDocumentType(): array
	{
		return CCrmBizProcHelper::ResolveDocumentType($this->DynamicTypeId);
	}

	public function makeResultFromId($id): ResultDto
	{
		$documentType = $this->getCreatedDocumentType();
		$documentId = $documentType;
		$documentId[2] = $id;

		/** @var CBPDocumentService $documentService */
		$documentService = CBPRuntime::GetRuntime()->GetService('DocumentService');
		$documentId = $documentService->normalizeDocumentId($documentId, $documentType[2]);

		$resultValue = [
			'DOCUMENT_ID' => $documentId,
			'DOCUMENT_TYPE' => $documentType,
		];

		return new ResultDto(get_class($this), $resultValue);
	}

	private function externalizeDocumentFields(): array
	{
		$documentType = CCrmBizProcHelper::ResolveDocumentType($this->DynamicTypeId);

		$documentService = static::getDocumentService();
		$documentFields = $documentService->getDocumentFields($documentType);
		$documentFieldsAliasesMap = CBPDocument::getDocumentFieldsAliasesMap($documentFields);

		$resultFields = [];
		foreach ($this->DynamicEntitiesFields as $key => $value)
		{
			if (!isset($documentFields[$key]) && isset($documentFieldsAliasesMap[$key]))
			{
				$key = $documentFieldsAliasesMap[$key];
			}

			$property = $documentFields[$key] ?? null;

			if ($property && !CBPHelper::isEmptyValue($value))
			{
				$fieldTypeObject = $documentService->getFieldTypeObject($documentType, $property);
				if ($fieldTypeObject)
				{
					$fieldTypeObject->setDocumentId($this->getDocumentId());
					$fieldTypeObject->setValue($value);
					$value = $fieldTypeObject->externalizeValue(
						\Bitrix\Bizproc\FieldType::VALUE_CONTEXT_DOCUMENT,
						$fieldTypeObject->getValue()
					);

					if (isset($value))
					{
						$resultFields[$key] = $value;
					}
				}
			}
		}

		return $resultFields;
	}

	private function logDocumentFields(array $fields)
	{
		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo(
				$this->getDebugInfo(
					$fields,
					array_intersect_key(static::getEntityFields($this->DynamicTypeId), $fields),
				)
			);
		}
	}

	private function bindElements(Crm\ItemIdentifier $parent, Crm\ItemIdentifier $child): void
	{
		$itemRelations = new Crm\Automation\Connectors\ItemRelations($child);
		if ($itemRelations->hasParentElement())
		{
			return;
		}
		$bindResult = $itemRelations->bindParentElement($parent);

		if (!$bindResult->isSuccess())
		{
			foreach ($bindResult->getErrorMessages() as $message)
			{
				$this->logError($message);
			}
		}
	}

	protected static function getFileName(): string
	{
		return __FILE__;
	}

	protected static function extractPropertiesValues(PropertiesDialog $dialog, array $fieldsMap): Result
	{
		$simpleMap = $fieldsMap;
		unset($simpleMap['DynamicEntitiesFields']);
		$result = parent::extractPropertiesValues($dialog, $simpleMap);

		if ($result->isSuccess())
		{
			$currentValues = $result->getData();
			$entityTypeId = (int)$currentValues['DynamicTypeId'];

			$originalDocType = $dialog->getDocumentType();
			$entityDocumentType = \CCrmBizProcHelper::ResolveDocumentType($entityTypeId);

			if ($entityDocumentType)
			{
				$dialog->setDocumentType($entityDocumentType);
			}

			$extractingFieldsResult = parent::extractPropertiesValues(
				$dialog,
				$fieldsMap['DynamicEntitiesFields']['Map'][$entityTypeId] ?? []
			);

			$dialog->setDocumentType($originalDocType);

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

		$currentModuleId = null;
		$currentEntityTypeId = null;

		if (isset($dialog))
		{
			$currentModuleId = $dialog->getDocumentType()[0];
			$currentEntityTypeId = CCrmOwnerType::ResolveID($dialog->getDocumentType()[2]) ?: null;
		}

		$typeNames = [];
		$entitiesFields = [];
		foreach ($typesMap->getFactories() as $factory)
		{
			$entityTypeId = $factory->getEntityTypeId();
			$documentType = CCrmBizProcHelper::ResolveDocumentType($entityTypeId);

			if (isset($documentType) && static::isTypeSupported($entityTypeId))
			{
				$typeNames[$entityTypeId] = static::getDocumentService()->getDocumentTypeName($documentType);
				$typeRelations = new Crm\Automation\Connectors\TypeRelations($factory);

				foreach (static::getEntityFieldsWithPrefix($entityTypeId) as $fieldId => $field)
				{
					$parentFieldName = $typeRelations->getParentFieldName($currentEntityTypeId ?? 0);
					$parentFieldName = "{$entityTypeId}_{$parentFieldName}";

					if (
						$currentModuleId === 'crm'
						&& $fieldId === $parentFieldName
						&& $typeRelations->isParentType($currentEntityTypeId)
					)
					{
						$entitiesFields[$entityTypeId]["{$entityTypeId}_BindToCurrentElement"] = [
							'Name' => Loc::getMessage('CRM_CDA_ITEM_FIELD_BIND_TO_CURRENT_ELEMENT'),
							'FieldName' => "{$entityTypeId}_bind_to_current_element",
							'Type' => FieldType::BOOL,
							'Default' => 'Y',
						];
					}

					$entitiesFields[$entityTypeId][$fieldId] = $field;
				}
			}
		}

		$showOnlyDynamicEntities = static::showOnlyDynamicEntities($dialog);

		return [
			'DynamicTypeId' => [
				'Name' => Loc::getMessage('CRM_CDA_TYPE_ID'),
				'FieldName' => 'dynamic_type_id',
				'Type' => FieldType::SELECT,
				'Options' => $showOnlyDynamicEntities ? static::getOnlyDynamicEntities($typeNames) : $typeNames,
				'Required' => true,
			],
			'DynamicEntitiesFields' => [
				'FieldName' => 'dynamic_entities_fields',
				'Map' => $entitiesFields,
				'Getter' => function($dialog, $property, $currentActivity, $compatible) {
					return $currentActivity['Properties']['DynamicEntitiesFields'];
				},
			],
			'OnlyDynamicEntities' => [
				'FieldName' => 'only_dynamic_entities',
				'Type' => 'bool',
				'Default' => $showOnlyDynamicEntities ? 'Y' : 'N',
				'Settings' => [
					'Hidden' => true,
				],
			],
		];
	}

	private static function getFieldIdWithoutPrefix(int $entityTypeId, string $fieldId): string
	{
		$fieldIdPrefixLength = mb_strlen($entityTypeId . '_');

		return mb_substr($fieldId, $fieldIdPrefixLength);
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		$map = static::getPropertiesDialogMap();
		unset($map['DynamicEntitiesFields'], $map['OnlyDynamicEntities']);

		return $map;
	}

	private static function isTypeSupported(int $entityTypeId): bool
	{
		return (bool)CCrmBizProcHelper::ResolveDocumentName($entityTypeId);
	}

	private static function getEntityFieldsWithPrefix(int $entityTypeId): array
	{
		$entityFields = [];
		foreach (static::getEntityFields($entityTypeId) as $fieldId => $field)
		{
			$field['FieldName'] = "{$entityTypeId}_{$field['FieldName']}";
			$entityFields["{$entityTypeId}_{$fieldId}"] = $field;
		}

		return $entityFields;
	}

	private static function getEntityFields(int $entityTypeId): array
	{
		$documentType = CCrmBizProcHelper::ResolveDocumentType($entityTypeId);
		$entityFields = [];

		foreach (static::getDocumentService()->GetDocumentFields($documentType) as $fieldId => $field)
		{
			$isIgnoredField = (
				!isset($field['Editable'])
				|| !$field['Editable']
				|| static::isInternalField($fieldId)
				|| static::isMultiField($field)
			);

			if (!$isIgnoredField || static::isRequiredFieldId($fieldId))
			{
				$entityFields[$fieldId] = $field;
				$entityFields[$fieldId]['FieldName'] = mb_strtolower($fieldId);
				$entityFields[$fieldId]['Type'] = $field['Type'] !== 'UF:date' ? $field['Type'] : 'date';
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

	private static function showOnlyDynamicEntities(?PropertiesDialog $dialog = null): bool
	{
		if (!$dialog)
		{
			return false;
		}

		$context = $dialog->getContext() ?? [];
		if (isset($context['addMenuGroup']) && $context['addMenuGroup'] === 'digitalWorkplace')
		{
			return true;
		}

		$workflowTemplate = $dialog->getWorkflowTemplate();
		$currentActivity = \CBPWorkflowTemplateLoader::FindActivityByName(
			$workflowTemplate,
			$dialog->getActivityName()
		);

		return (
			is_array($currentActivity)
			&& is_array($currentActivity['Properties'])
			&& ($currentActivity['Properties']['OnlyDynamicEntities'] ?? 'N') === 'Y'
		);
	}

	private static function getOnlyDynamicEntities(array $dynamicTypeIdOptions): array
	{
		return array_filter(
			$dynamicTypeIdOptions,
			static function($key) {
				return (CCrmOwnerType::isPossibleDynamicTypeId($key));
			},
			ARRAY_FILTER_USE_KEY
		);
	}
}

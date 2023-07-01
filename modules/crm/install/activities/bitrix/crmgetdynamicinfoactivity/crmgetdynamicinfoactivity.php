<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bizproc\Automation\Engine\ConditionGroup;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Crm;
use Bitrix\Crm\Integration\BizProc\Document;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class CBPCrmGetDynamicInfoActivity extends \Bitrix\Bizproc\Activity\BaseActivity
{
	protected static $requiredModules = ['crm'];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'DynamicTypeId' => 0,
			'DynamicFilterFields' => ['items' => []],
			'ReturnFields' => [],
			'OnlyDynamicEntities' => 'N',

			// return
			'DynamicEntityFields' => null,
		];
	}

	protected function prepareProperties(): void
	{
		parent::prepareProperties();

		try
		{
			$this->preparedProperties['DynamicId'] = $this->findEntityId();
		}
		catch (\Bitrix\Main\ArgumentException $exception)
		{
			$this->preparedProperties['DynamicId'] = 0;
		}
	}

	protected function findEntityId(): int
	{
		$filter = ['LOGIC' => 'OR'];

		$conditionGroup = new ConditionGroup($this->DynamicFilterFields);
		$conditionGroup->internalizeValues($this->getDocumentType());

		$complexDocumentType = CCrmBizProcHelper::ResolveDocumentType($this->DynamicTypeId);
		$fieldsMap = static::getDocumentService()->GetDocumentFields($complexDocumentType);
		$i = 0;

		/**@var \Bitrix\Bizproc\Automation\Engine\Condition $condition*/
		foreach ($conditionGroup->getItems() as [$condition, $joiner])
		{
			if (!isset($fieldsMap[$condition->getField()]))
			{
				continue;
			}

			$value = $this->convertToDocumentValue($fieldsMap[$condition->getField()], $condition->getValue());
			switch ($condition->getOperator())
			{
				case 'empty':
					$operator = '=';
					$value = '';
					break;

				case '!empty':
					$operator = '!=';
					$value = '';
					break;

				case 'in':
					$operator = '@';
					break;

				case '!in':
					$operator = '!@';
					break;

				case 'contain':
					$operator = '%';
					break;

				case '!contain':
					$operator = '!%';
					break;

				case '>':
				case '>=':
				case '<':
				case '<=':
				case '=':
				case '!=':
					$operator = $condition->getOperator();
					break;

				default:
					$operator = '';
					break;
			}

			if (!$operator)
			{
				continue;
			}

			if ($joiner === ConditionGroup::JOINER_AND)
			{
				$filter[$i][$operator . $condition->getField()] = $value;
			}
			else
			{
				$filter[++$i][$operator . $condition->getField()] = $value;
			}
		}

		$factory = Container::getInstance()->getFactory($this->DynamicTypeId);
		$items = [];
		if (isset($factory))
		{
			$items = $factory->getItems([
				'select' => ['ID'],
				'filter' => $filter,
			]);
		}

		return $items ? $items[0]->getId() : 0;
	}

	protected function convertToDocumentValue(array $fieldInfo, $fieldValue)
	{
		if (is_array($fieldValue) && !$fieldInfo['Multiple'])
		{
			$fieldValue = reset($fieldValue);
		}

		if (is_array($fieldValue))
		{
			foreach ($fieldValue as $key => $value)
			{
				$fieldValue[$key] = $this->convertToDocumentValue($fieldInfo, $value);
			}

			return $fieldValue;
		}

		switch ($fieldInfo['Type'])
		{
			case FieldType::BOOL:
				return CBPHelper::getBool($fieldValue);

			case FieldType::USER:
				return CBPHelper::extractUsers($fieldValue, $this->getDocumentId(), !$fieldInfo['Multiple']) ?? 0;

			case FieldType::INT:
				return (int)$fieldValue;

			case FieldType::DOUBLE:
				return (float)$fieldValue;

			default:
				return $fieldValue;
		}
	}

	protected function checkProperties(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::checkProperties();

		if (!CCrmBizProcHelper::ResolveDocumentName($this->DynamicTypeId))
		{
			$errors->setError(new Error(Loc::getMessage('CRM_GDIA_ENTITY_TYPE_ERROR')));
		}
		if ($this->DynamicId <= 0)
		{
			$errors->setError(new Error(Loc::getMessage('CRM_GDIA_ENTITY_EXISTENCE_ERROR')));
		}

		return $errors;
	}

	protected function internalExecute(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::internalExecute();

		$complexDocumentId = CCrmBizProcHelper::ResolveDocumentId($this->DynamicTypeId, $this->DynamicId);
		$document = static::getDocumentService()->GetDocument($complexDocumentId);
		foreach ($this->ReturnFields as $fieldId)
		{
			$this->arProperties[$fieldId] = $document[$fieldId];
			$this->preparedProperties[$fieldId] = $document[$fieldId];
		}
		$this->setPropertiesTypes($this->DynamicEntityFields);

		return $errors;
	}

	protected static function getFileName(): string
	{
		return __FILE__;
	}

	protected static function extractPropertiesValues(PropertiesDialog $dialog, array $fieldsMap): Result
	{
		$simpleMap = $fieldsMap;
		unset($simpleMap['DynamicFilterFields']);
		$result = parent::extractPropertiesValues($dialog, $simpleMap);

		if ($result->isSuccess())
		{
			$currentValues = $result->getData();
			$entityTypeId = (int)$currentValues['DynamicTypeId'];

			$currentValues['DynamicFilterFields'] = static::extractFieldsConditions($dialog, $fieldsMap);
			$currentValues['ReturnFields'] = $dialog->getCurrentValue('return_fields', []);

			$returnFieldsMap = static::getReturnFieldsMap($entityTypeId);
			foreach ($dialog->getCurrentValue('return_fields', []) as $fieldId)
			{
				if (isset($returnFieldsMap[$fieldId]))
				{
					$currentValues['DynamicEntityFields'][$fieldId] = $returnFieldsMap[$fieldId];
				}
			}

			$result->setData($currentValues);
		}

		return $result;
	}

	protected static function extractFieldsConditions(PropertiesDialog $dialog, array $fieldsMap): array
	{
		$currentValues = $dialog->getCurrentValues();
		$prefix = $fieldsMap['DynamicFilterFields']['FieldName'] . '_';

		$conditionGroup = ['items' => []];

		foreach ($currentValues[$prefix . 'field'] ?? [] as $index => $fieldName)
		{
			$conditionGroup['items'][] = [
				// condition
				[
					'object' => $currentValues[$prefix . 'object'][$index],
					'field' => $currentValues[$prefix . 'field'][$index],
					'operator' => $currentValues[$prefix . 'operator'][$index],
					'value' => $currentValues[$prefix . 'value'][$index],
				],
				// joiner
				$currentValues[$prefix . 'joiner'][$index],
			];
		}

		return $conditionGroup;
	}

	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$workflowTemplate,
		$workflowParameters,
		$workflowVariables,
		$currentValues = null,
		$formName = '',
		$popupWindow = null,
		$siteId = ''
	)
	{
		$dialog = parent::GetPropertiesDialog(...func_get_args());
		$dialog->setRuntimeData([
			'DocumentName' => static::getDocumentService()->getEntityName('crm', $documentType[1]),
			'DocumentFields' => array_values(Crm\Automation\Helper::getDocumentFields($documentType)),
		]);

		return $dialog;
	}

	protected static function getReturnFieldsMap(int $entityTypeId): array
	{
		$fieldsMap = [];
		$documentType = CCrmBizProcHelper::ResolveDocumentType($entityTypeId);

		try
		{
			if (isset($documentType))
			{
				$fieldsMap = array_filter(
					static::getDocumentService()->GetDocumentFields($documentType),
					static function ($fieldId) {
						return !static::isInternalField($fieldId);
					},
					ARRAY_FILTER_USE_KEY
				);
			}
		}
		catch (\Bitrix\Main\ArgumentException $exception) {}

		return $fieldsMap;
	}

	public static function getPropertiesDialogMap(?\Bitrix\Bizproc\Activity\PropertiesDialog $dialog = null): array
	{
		$typeNames = [];
		$filteringFieldsMap = [];
		$returnFieldsMap = [];

		$typesMap = Container::getInstance()->getTypesMap();

		foreach ($typesMap->getFactories() as $factory)
		{
			$entityTypeId = $factory->getEntityTypeId();
			$documentType = CCrmBizProcHelper::ResolveDocumentType($entityTypeId);

			if (isset($documentType))
			{
				$typeNames[$entityTypeId] = static::getDocumentService()->getDocumentTypeName($documentType);
				$returnFieldsMap[$entityTypeId] = static::getReturnFieldsMap($entityTypeId);
				$filteringFieldsMap[$entityTypeId] = static::getFilteringFieldsMap($entityTypeId);
			}
		}

		$showOnlyDynamicEntities = static::showOnlyDynamicEntities($dialog);

		return [
			'DynamicTypeId' => [
				'Name' => Loc::getMessage('CRM_GDIA_TYPE_ID'),
				'FieldName' => 'dynamic_type_id',
				'Type' => FieldType::SELECT,
				'Options' => $showOnlyDynamicEntities ? static::getOnlyDynamicEntities($typeNames) : $typeNames,
				'Required' => true
			],
			'ReturnFields' => [
				'Name' => Loc::getMessage('CRM_GDIA_RETURN_FIELDS_SELECTION'),
				'FieldName' => 'return_fields',
				'Type' => FieldType::SELECT,
				'Options' => [],
				'Multiple' => true,
				'Required' => true,
				'Map' => $returnFieldsMap,
				'Getter' => function($dialog, $property, $currentActivity, $compatible) {
					return $currentActivity['Properties']['ReturnFields'];
				},
			],
			'DynamicFilterFields' => [
				'Name' => Loc::getMessage('CRM_GDIA_FILTERING_FIELDS_PROPERTY'),
				'FieldName' => 'dynamic_filter_fields',
				'Map' => $filteringFieldsMap,
				'Getter' => function($dialog, $property, $currentActivity, $compatible) {
					return $currentActivity['Properties']['DynamicFilterFields'];
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

	protected static function getFilteringFieldsMap(int $entityTypeId): array
	{
		$documentType = CCrmBizProcHelper::ResolveDocumentType($entityTypeId);

		$factory = Container::getInstance()->getFactory($entityTypeId);
		$originalFieldsCollection = isset($factory) ? $factory->getFieldsCollection() : null;

		$map = [];

		$supportedFieldTypes = [
			FieldType::DOUBLE,
			FieldType::INT,
			FieldType::USER,
			FieldType::STRING,
			FieldType::BOOL,
		];
		foreach (Crm\Automation\Helper::getDocumentFields($documentType) as $fieldId => $field)
		{
			if ($fieldId === 'OBSERVER_IDS')
			{
				$fieldId = Crm\Item::FIELD_NAME_OBSERVERS;
			}

			$isEntityField = true;
			if (isset($originalFieldsCollection))
			{
				$isEntityField = $originalFieldsCollection->hasField($factory->getCommonFieldNameByMap($fieldId));
			}
			if (
				in_array($field['Type'], $supportedFieldTypes, true)
				&& !static::isInternalField($fieldId)
				&& $isEntityField
			)
			{
				$map[] = $field;
			}
		}

		return $map;
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

	private static function showOnlyDynamicEntities(?PropertiesDialog $dialog = null): bool
	{
		if (!$dialog)
		{
			return false;
		}

		$context = $dialog->getContext() ?? [];
		if ($context['addMenuGroup'] === 'digitalWorkplace')
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
			&& $currentActivity['Properties']['OnlyDynamicEntities'] === 'Y'
		);
	}

	private static function getOnlyDynamicEntities(array $dynamicTypeIdOptions): array
	{
		return array_filter(
			$dynamicTypeIdOptions,
			static function($key) {
				return ($key >= CCrmOwnerType::DynamicTypeStart && $key <= CCrmOwnerType::DynamicTypeEnd);
			},
			ARRAY_FILTER_USE_KEY
		);
	}
}
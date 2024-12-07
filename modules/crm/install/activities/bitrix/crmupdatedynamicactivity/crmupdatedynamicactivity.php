<?php

use Bitrix\Bizproc\Automation\Engine\ConditionGroup;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Crm;
use Bitrix\Crm\Integration\BizProc\Document;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @property-write int DynamicTypeId
 * @property-write int DynamicId
 * @property-write array DynamicFilterFields
 * @property-write array DynamicEntitiesFields
 */
class CBPCrmUpdateDynamicActivity extends \Bitrix\Bizproc\Activity\BaseActivity
{
	use \Bitrix\Bizproc\Activity\Mixins\EntityFilter;

	protected static $requiredModules = ['crm'];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'DynamicTypeId' => 0,
			'DynamicId' => 0,
			'DynamicFilterFields' => ['items' => []],
			'DynamicEntitiesFields' => [],
		];

		$this->SetPropertiesTypes([
			'DynamicTypeId' => ['Type' => FieldType::INT],
		]);
	}

	protected function prepareProperties(): void
	{
		parent::prepareProperties();

		if ((int)$this->getRawProperty('DynamicId') !== 0)
		{
			array_unshift($this->preparedProperties['DynamicFilterFields']['items'], [
				[
					'object' => 'Document',
					'field' => 'ID',
					'operator' => '=',
					'value' => $this->DynamicId,
				],
				ConditionGroup::JOINER_AND,
			]);

			$this->DynamicId = 0;
		}

		$this->preparedProperties['DynamicId'] = $this->findEntityId();
	}

	protected function findEntityId(): int
	{
		$conditionGroup = new ConditionGroup($this->DynamicFilterFields);
		$conditionGroup->internalizeValues($this->getDocumentType());

		$targetDocumentType = CCrmBizProcHelper::ResolveDocumentType($this->DynamicTypeId);

		if (!$targetDocumentType)
		{
			return 0;
		}

		$factory = Container::getInstance()->getFactory($this->DynamicTypeId);
		$items = $factory?->getItems([
			'select' => ['ID'],
			'filter' => $this->getOrmFilter($conditionGroup, $targetDocumentType),
			'limit' => 1,
		]);

		return $items ? $items[0]->getId() : 0;
	}

	protected function checkProperties(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::checkProperties();

		if (
			is_null($this->DynamicTypeId)
			|| !CCrmOwnerType::isPossibleDynamicTypeId($this->DynamicTypeId)
			|| is_null(Container::getInstance()->getFactory($this->DynamicTypeId))
		)
		{
			$errors->setError(new Error(Loc::getMessage('CRM_UDA_ENTITY_TYPE_ERROR')));
		}
		if ($this->DynamicId <= 0)
		{
			$errors->setError(new Error(Loc::getMessage('CRM_UDA_ENTITY_EXISTENCE_ERROR')));
		}

		return $errors;
	}

	protected function internalExecute(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::internalExecute();

		$documentId = CCrmBizProcHelper::ResolveDocumentId($this->DynamicTypeId, $this->DynamicId);

		$updateResult = static::getDocumentService()->updateDocument($documentId, $this->DynamicEntitiesFields);
		if (is_string($updateResult))
		{
			$errors->setError(new Error($updateResult));
		}

		return $errors;
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

		$entityTypeId = (int)$dialog->getCurrentValue('dynamic_type_id', 0);
		$entityId = (int)$dialog->getCurrentValue('dynamic_id', 0);
		$entityFieldsMap = $dialog->getMap()['DynamicEntitiesFields']['Map'][$entityTypeId] ?? null;
		$entityFieldsValues = $dialog->getCurrentValue('dynamic_entities_fields');

		$preparedValues = $dialog->getCurrentValues();

		if ($entityId !== 0)
		{
			$filterFields = $dialog->getCurrentValue('dynamic_filter_fields', ['items' => []]);
			array_unshift($filterFields['items'], [
				[
					'object' => 'Document',
					'field' => 'ID',
					'operator' => '=',
					'value' => $entityId,
				],
				ConditionGroup::JOINER_AND,
			]);

			$preparedValues['dynamic_filter_fields'] = $filterFields;
			unset($preparedValues['dynamic_id']);
		}
		if (is_array($entityFieldsMap) && is_array($entityFieldsValues))
		{
			foreach ($entityFieldsMap as $field)
			{
				$fieldName = $field['FieldName'];
				if ($field['Type'] === FieldType::USER && array_key_exists($fieldName, $entityFieldsValues))
				{
					$entityFieldsValues[$fieldName] = CBPHelper::UsersArrayToString(
						$entityFieldsValues[$fieldName],
						$dialog->getWorkflowTemplate(),
						$dialog->getDocumentType()
					);
				}
			}

			$preparedValues['dynamic_entities_fields'] = $entityFieldsValues;
		}

		return $dialog->setCurrentValues($preparedValues);
	}

	protected static function getFileName(): string
	{
		return __FILE__;
	}

	protected static function extractPropertiesValues(PropertiesDialog $dialog, array $fieldsMap): Result
	{
		$simpleMap = $fieldsMap;
		unset($simpleMap['DynamicFilterFields'], $simpleMap['DynamicEntitiesFields']);
		$result = parent::extractPropertiesValues($dialog, $simpleMap);

		if ($result->isSuccess())
		{
			$currentValues = $result->getData();
			$entityTypeId = (int)$currentValues['DynamicTypeId'];

			$extractingFilterResult = static::extractFilterFromProperties($dialog, $fieldsMap);
			if ($extractingFilterResult->isSuccess())
			{
				$currentValues['DynamicFilterFields'] = $extractingFilterResult->getData();
			}

			$extractingFieldsResult = parent::extractPropertiesValues(
				$dialog,
				array_intersect_ukey(
					$fieldsMap['DynamicEntitiesFields']['Map'][$entityTypeId] ?? [],
					$dialog->getCurrentValues(),
					function ($lhsKey, $rhsKey) {
						if (mb_substr($lhsKey, -mb_strlen('_text')) === '_text')
						{
							$lhsKey = mb_substr($lhsKey, 0, mb_strlen($lhsKey) - mb_strlen('_text'));
						}
						if (mb_substr($rhsKey, -mb_strlen('_text')) === '_text')
						{
							$rhsKey = mb_substr($rhsKey, 0, mb_strlen($rhsKey) - mb_strlen('_text'));
						}

						return strcmp($lhsKey, $rhsKey);
					},
				)
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
		$typesMap = Container::getInstance()->getDynamicTypesMap();
		$typesMap->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		$typeNames = [];
		$entitiesFieldsMap = [];
		$filteringFieldsMap = [];
		foreach ($typesMap->getTypes() as $type)
		{
			$entityTypeId = $type->getEntityTypeId();

			$typeNames[$entityTypeId] = $type->getTitle();
			$filteringFieldsMap[$entityTypeId] = static::getFilteringFieldsMap($entityTypeId);
			$entitiesFieldsMap[$entityTypeId] = static::getDocumentFieldsMap($entityTypeId);
		}

		return [
			'DynamicTypeId' => [
				'Name' => Loc::getMessage('CRM_UDA_DYNAMIC_TYPE'),
				'FieldName' => 'dynamic_type_id',
				'Type' => FieldType::SELECT,
				'Options' => $typeNames,
				'Required' => true,
				'AllowSelection' => false,
			],
			'DynamicId' => [
				'FieldName' => 'dynamic_id',
				'Type' => FieldType::INT,
			],
			'DynamicFilterFields' => [
				'Name' => Loc::getMessage('CRM_UDA_FILTERING_FIELDS_PROPERTY'),
				'FieldName' => 'dynamic_filter_fields',
				'Map' => $filteringFieldsMap,
				'Getter' => function($dialog, $property, $currentActivity, $compatible) {
					return $currentActivity['Properties']['DynamicFilterFields'];
				},
			],
			'DynamicEntitiesFields' => [
				'FieldName' => 'dynamic_entities_fields',
				'Map' => $entitiesFieldsMap,
				'Getter' => function($dialog, $property, $currentActivity, $compatible) {
					return $currentActivity['Properties']['DynamicEntitiesFields'];
				},
			],
		];
	}

	protected static function getFilteringFieldsMap(int $entityTypeId): array
	{
		$documentType = CCrmBizProcHelper::ResolveDocumentType($entityTypeId);

		$factory = Container::getInstance()->getFactory($entityTypeId);
		$originalFieldsCollection = $factory->getFieldsCollection();

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
			$fieldId = Document\Item::convertFieldId($fieldId, Document\Item::CONVERT_TO_DOCUMENT);
			if (
				in_array($field['Type'], $supportedFieldTypes, true)
				&& !static::isInternalField($fieldId)
				&& $originalFieldsCollection->hasField($factory->getCommonFieldNameByMap($fieldId))
			)
			{
				$map[] = $field;
			}
		}

		return $map;
	}

	protected static function getDocumentFieldsMap(int $entityTypeId): array
	{
		$fieldsMap = [];
		foreach (Document\Dynamic::getEntityFields($entityTypeId) as $fieldId => $field)
		{
			if ($field['Editable'] && !static::isInternalField($fieldId))
			{
				$field['FieldName'] = $fieldId;
				$fieldsMap[$fieldId] = $field;
			}
		}

		return $fieldsMap;
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
}
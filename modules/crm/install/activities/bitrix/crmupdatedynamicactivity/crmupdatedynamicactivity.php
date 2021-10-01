<?php

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

class CBPCrmUpdateDynamicActivity extends \Bitrix\Bizproc\Activity\BaseActivity
{
	protected static $requiredModules = ['crm'];

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'DynamicTypeId' => 0,
			'DynamicId' => 0,
			'DynamicEntitiesFields' => [],
		];

		$this->SetPropertiesTypes([
			'DynamicTypeId' => ['Type' => FieldType::INT],
		]);
	}

	protected function checkProperties(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::checkProperties();

		$factory = Container::getInstance()->getFactory($this->DynamicTypeId);

		if (!CCrmOwnerType::isPossibleDynamicTypeId($this->DynamicTypeId) || is_null($factory))
		{
			$errors->setError(new Error(Loc::getMessage('CRM_UDA_ENTITY_TYPE_ERROR')));
		}
		if ($this->DynamicId <= 0)
		{
			$errors->setError(new Error(Loc::getMessage('CRM_UDA_ENTITY_ID_ERROR')));
		}

		$item = isset($factory) ? $factory->getItem($this->DynamicId) : null;
		if (is_null($item) && $errors->isEmpty())
		{
			$errorMessage = Loc::getMessage('CRM_UDA_ENTITY_EXISTENCE_ERROR', [
				'#TYPE_NAME#' => $factory->getEntityDescription(),
				'#ENTITY_ID#' => $this->DynamicId,
			]);
			$errors->setError(new Error($errorMessage));
		}

		return $errors;
	}

	protected function internalExecute(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::internalExecute();

		$entityType = CCrmOwnerType::ResolveName($this->DynamicTypeId);
		$documentId = $entityType . '_' . $this->DynamicId;

		$updateResult = Document\Dynamic::UpdateDocument($documentId, $this->DynamicEntitiesFields);
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

		$entityTypeId = (int)$dialog->getCurrentValue('dynamic_type_id', 0);
		$entityFieldsMap = $dialog->getMap()['DynamicEntitiesFields']['Map'][$entityTypeId] ?? null;
		$entityFieldsValues = $dialog->getCurrentValue('dynamic_entities_fields');

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

			$preparedValues = $dialog->getCurrentValues();
			$preparedValues['dynamic_entities_fields'] = $entityFieldsValues;

			$dialog->setCurrentValues($preparedValues);
		}

		return $dialog;
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
				array_intersect_key(
					$fieldsMap['DynamicEntitiesFields']['Map'][$entityTypeId] ?? [],
					$dialog->getCurrentValues()
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
		foreach ($typesMap->getTypes() as $type)
		{
			$entityTypeId = $type->getEntityTypeId();

			$typeNames[$entityTypeId] = $type->getTitle();
			$entitiesFieldsMap[$entityTypeId] = static::getDocumentFieldsMap($entityTypeId);
		}

		return [
			'DynamicTypeId' => [
				'Name' => Loc::getMessage('CRM_UDA_DYNAMIC_TYPE'),
				'FieldName' => 'dynamic_type_id',
				'Type' => FieldType::SELECT,
				'Options' => $typeNames,
				'Required' => true
			],
			'DynamicId' => [
				'Name' => Loc::getMessage('CRM_UDA_DYNAMIC_ID'),
				'FieldName' => 'dynamic_id',
				'Type' => FieldType::INT,
				'Required' => true
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
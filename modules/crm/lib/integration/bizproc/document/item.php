<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Bizproc\FieldType;
use Bitrix\Crm;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

if (!Loader::includeModule('bizproc'))
{
	return;
}

class Item extends \CCrmDocument implements \IBPWorkflowDocument
{
	public const CONVERT_TO_BP = 0;
	public const CONVERT_TO_DOCUMENT = 1;

	public static function getDocumentTypeName($documentType)
	{
		if (is_string($documentType))
		{
			$typeId = \CCrmOwnerType::ResolveID($documentType);
			$factory = Container::getInstance()->getFactory($typeId);

			return isset($factory) ? $factory->getEntityDescription() : null;
		}

		return null;
	}

	public static function GetDocumentAdminPage($documentId)
	{
		$documentInfo = static::GetDocumentInfo($documentId);

		return Container::getInstance()->getRouter()->getItemDetailUrl($documentInfo['TYPE_ID'], $documentInfo['ID']);
	}

	public static function GetUserFields(Crm\Service\Factory $factory, $langId = false)
	{
		return Application::getUserTypeManager()->GetUserFields($factory->getUserFieldEntityId(), 0, $langId);
	}

	public static function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = [])
	{
		$entityTypeId = static::GetDocumentInfo($documentType)['TYPE_ID'];

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (
			isset($factory)
			&& $factory->isCategoriesSupported()
			&& !isset($arParameters['DocumentCategoryId'])
		)
		{
			$arParameters['DocumentCategoryId'] = $factory->createDefaultCategoryIfNotExist()->getId();;
		}

		return parent::CanUserOperateDocumentType(
			$operation,
			$userId,
			$documentType,
			$arParameters
		);
	}

	public static function addProductRows(string $documentId, array $productRows): Result
	{
		$documentInfo = static::GetDocumentInfo($documentId);
		$result = new Result();

		if (!$documentInfo)
		{
			$result->addError(new Error('Invalid document type'));
		}
		else
		{
			$factory = Container::getInstance()->getFactory($documentInfo['TYPE_ID']);
			$item = isset($factory) ? $factory->getItem($documentInfo['ID']) : null;
		}

		if (!isset($factory, $item))
		{
			$errorMessage = Loc::getMessage('CRM_ENTITY_EXISTENCE_ERROR', ['#DOCUMENT_ID#' => $documentId]);
			$result->addError(new Error($errorMessage));
		}
		elseif (!$factory->isLinkWithProductsEnabled())
		{
			$result->addError(new Error(Loc::getMessage('CRM_BP_DOCUMENT_ITEM_LINK_WIH_PRODUCTS_DISABLED_ERROR')));
		}

		if ($result->isSuccess())
		{
			foreach ($productRows as $row)
			{
				$addResult = $item->addToProductRows($row);
				if (!$addResult->isSuccess())
				{
					$result->addErrors($addResult->getErrors());
				}
			}

			if ($result->isSuccess())
			{
				$operation = $factory->getUpdateOperation($item, static::getContext());
				$result = static::launchOperation($operation);
			}
		}

		return $result;
	}

	public static function setProductRows(string $documentId, array $productRows): Result
	{
		$documentInfo = static::GetDocumentInfo($documentId);
		$result = new Result();

		if (!$documentInfo)
		{
			$result->addError(new Error('Invalid document id'));
		}
		else
		{
			$factory = Container::getInstance()->getFactory($documentInfo['TYPE_ID']);
			$item = isset($factory) ? $factory->getItem($documentInfo['ID']) : null;
		}

		if (!isset($factory, $item))
		{
			$errorMessage = Loc::getMessage('CRM_ENTITY_EXISTENCE_ERROR', ['#DOCUMENT_ID#' => $documentId]);
			$result->addError(new Error($errorMessage));
		}
		elseif (!$factory->isLinkWithProductsEnabled())
		{
			$result->addError(new Error(Loc::getMessage('CRM_BP_DOCUMENT_ITEM_LINK_WIH_PRODUCTS_DISABLED_ERROR')));
		}

		if (isset($factory, $item))
		{
			$result = $item->setProductRows($productRows);
		}
		else
		{
			$errorMessage = Loc::getMessage('CRM_ENTITY_EXISTENCE_ERROR', ['#DOCUMENT_ID#' => $documentId]);
			$result->addError(new Error($errorMessage));
		}

		if (isset($factory, $item) && $result->isSuccess())
		{
			$result = static::launchOperation($factory->getUpdateOperation($item));
		}

		return $result;
	}

	public static function CreateDocument($parentDocumentId, $fields)
	{
		$entityTypeId = static::GetDocumentInfo($parentDocumentId)['TYPE_ID'];

		$factory = Container::getInstance()->getFactory($entityTypeId);
		$newItem = $factory->createItem([]);

		$fieldsCaster = new Crm\Automation\Fields\ItemFieldsCaster($newItem, static::getEntityFields($entityTypeId));
		$compatibleFields = $fieldsCaster->externalize($fields);

		$userId = $compatibleFields[Crm\Item::FIELD_NAME_CREATED_BY] ?? 0;
		$newItem->setFromCompatibleData($compatibleFields);
		$addOperation = $factory->getAddOperation($newItem, static::getContext($userId));

		$result = static::launchOperation($addOperation);
		$errorMessages = $result->getErrorMessages();

		return $result->isSuccess() ? $result->getId() : end($errorMessages);
	}

	public static function UpdateDocument($documentId, $fields, $modifiedBy = null)
	{
		$documentInfo = static::GetDocumentInfo($documentId);
		if (!$documentInfo)
		{
			throw new ArgumentNullException('documentId');
		}
		if (!is_int($modifiedBy))
		{
			$modifiedBy = 0;
		}
		[$entityTypeId, $entityId] = [$documentInfo['TYPE_ID'], $documentInfo['ID']];

		$factory = Container::getInstance()->getFactory($entityTypeId);
		$item = isset($factory) ? $factory->getItem($entityId) : null;

		if (is_null($item))
		{
			$errorMessage = Loc::getMessage('CRM_ENTITY_EXISTENCE_ERROR', ['#DOCUMENT_ID#', $documentId]);
			throw new ArgumentException($errorMessage);
		}

		$fieldCaster = new Crm\Automation\Fields\ItemFieldsCaster($item, static::getEntityFields($entityTypeId));
		$item->setFromCompatibleData($fieldCaster->externalize($fields));

		$updateOperation = $factory->getUpdateOperation($item, static::getContext($modifiedBy));

		$result = static::launchOperation($updateOperation);
		$errorMessages = $result->getErrorMessages();

		return $result->isSuccess() ?: end($errorMessages);
	}

	protected static function isUserField(string $fieldId): bool
	{
		return mb_substr($fieldId, 0, 3) === 'UF_';
	}

	public static function DeleteDocument($documentId)
	{
		$documentInfo = static::GetDocumentInfo($documentId);
		[$entityTypeId, $entityId] = [$documentInfo['TYPE_ID'], $documentInfo['ID']];

		$factory = Container::getInstance()->getFactory($entityTypeId);
		$item = isset($factory) ? $factory->getItem($entityId) : null;

		if (is_null($item))
		{
			$errorMessage = Loc::getMessage('CRM_ENTITY_EXISTENCE_ERROR', ['#DOCUMENT_ID#', $documentId]);
			throw new ArgumentException($errorMessage);
		}

		$deleteOperation = $factory->getDeleteOperation($item, static::getContext());

		return static::launchOperation($deleteOperation->disableBizProc());
	}

	protected static function launchOperation(Operation $operation): Result
	{
		$dbConnection = Application::getConnection();
		if (static::shouldUseTransaction())
		{
			$dbConnection->startTransaction();
		}

		$isBizProcEnabled = $operation->isBizProcEnabled();

		// BizProc is disabled because it will be launched differently further
		$operation->disableBizProc()->disableCheckFields()->disableCheckAccess();
		$operationResult = $operation->launch();

		if (
			$operationResult->isSuccess()
			&& $isBizProcEnabled
			&& \COption::GetOptionString('crm', 'start_bp_within_bp', 'N') === 'Y'
		)
		{
			$item = $operation->getItem();
			$itemType = \CCrmOwnerType::ResolveName($item->getEntityTypeId());
			$itemId = $item->isNew() ? false : $item->getId();
			$documentId = $item->isNew() ? false : $itemType . '_' . $item->getId();

			$bizProc = new \CCrmBizProc($itemType);
			if (!$bizProc->CheckFields($documentId, true) || !$bizProc->StartWorkflow($itemId))
			{
				$operationResult->addError(new Error($bizProc->LAST_ERROR));
			}
		}

		if (static::shouldUseTransaction())
		{
			$operationResult->isSuccess() ? $dbConnection->commitTransaction() : $dbConnection->rollbackTransaction();
		}

		return $operationResult;
	}

	protected static function getContext(int $userId = 0): Crm\Service\Context
	{
		$context = clone Container::getInstance()->getContext();
		$context->setUserId($userId);
		$context->setScope(Crm\Service\Context::SCOPE_AUTOMATION);

		return $context;
	}

	public static function getDocumentName($documentId)
	{
		$documentInfo = static::GetDocumentInfo($documentId);

		$factory = Container::getInstance()->getFactory($documentInfo['TYPE_ID']);
		$item = isset($factory) ? $factory->getItem($documentInfo['ID']) : null;

		return isset($item) ? $item->getHeading() : '';
	}

	public static function normalizeDocumentId($documentId, string $docType = null)
	{
		if ($docType && is_numeric($documentId))
		{
			$documentId = $docType . '_' . $documentId;
		}

		$documentInfo = static::GetDocumentInfo($documentId);

		return parent::normalizeDocumentIdInternal(
			$documentId,
			$documentInfo['TYPE'],
			$documentInfo['TYPE_ID']
		);
	}

	public static function createAutomationTarget($documentType)
	{
		$typeId = static::GetDocumentInfo($documentType)['TYPE_ID'];

		return Crm\Automation\Factory::createTarget($typeId);
	}

	public static function GetDocumentFields($documentType)
	{
		$entityTypeId = static::GetDocumentInfo($documentType)['TYPE_ID'];

		return static::getEntityFields($entityTypeId);
	}

	public static function prepareCompatibleData(array $compatibleData): array
	{
		return Crm\Automation\Fields\ItemFieldsCaster::internalizeFieldsIds($compatibleData);
	}

	public static function getEntityFields($entityTypeId)
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (is_null($factory))
		{
			throw new \CBPArgumentException(Loc::getMessage('CRM_BP_DOCUMENT_ITEM_ENTITY_TYPE_ERROR'));
		}
		$entityFields = static::getVirtualFields();

		foreach ($factory->getFieldsInfo() as $fieldId => $field)
		{
			if (!isset($field['TYPE']))
			{
				continue;
			}
			$fieldId = $factory->getEntityFieldNameByMap($fieldId);

			$editable =
				!\CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::ReadOnly)
				&& !\CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::Immutable)
				&& static::isEditableField($field)
			;

			$required = \CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::Required);
			$multiple = \CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::Multiple);

			$singleEntityField = [
				'Name' => static::getFieldName($factory, $fieldId),
				'Type' => static::resolveBPType($field['TYPE']),
				'Options' => static::getFieldOptions($field, $factory),
				'Settings' => static::getFieldSettings($field, $factory),
				'Editable' => $editable,
				'Required' => $required,
				'Multiple' => $multiple,
			];

			if (
				$field['TYPE'] === Crm\Field::TYPE_TEXT
				&& isset($field['VALUE_TYPE'])
				&& in_array($field['VALUE_TYPE'], [Crm\Field::VALUE_TYPE_HTML, Crm\Field::VALUE_TYPE_BB], true)
			)
			{
				$singleEntityField['ValueContentType'] = $field['VALUE_TYPE'];
			}

			$entityFields[static::convertFieldId($fieldId)] = $singleEntityField;
		}
		static::fitEntityFieldsToInterface($entityFields);

		$entityFields += array_merge(
			static::getAssignedByFields(),
			static::getCommunicationFields(),
			static::getUserFieldsMap($factory),
			static::getUtmFields(),
			static::getSiteFormFields($factory->getEntityTypeId()),
		);

		return $entityFields;
	}

	private static function getUserFieldsMap(Crm\Service\Factory $factory): array
	{
		$userFields = [];

		$CCrmUserType = new \CCrmUserType(Application::getUserTypeManager(), $factory->getUserFieldEntityId());
		$CCrmUserType->AddBPFields(
			$userFields,
			['PRINTABLE_SUFFIX' => Loc::getMessage('CRM_FIELD_BP_TEXT')]
		);

		foreach ($userFields as &$field)
		{
			if ($field['Type'] === 'UF:date')
			{
				$field['Type'] = 'date';
			}
		}

		return $userFields;
	}

	protected static function isEditableField(array $field): bool
	{
		return $field['TYPE'] !== Crm\Field::TYPE_CRM_PRODUCT_ROW;
	}

	public static function convertFieldId(string $fieldId, int $convertTo = self::CONVERT_TO_BP): string
	{
		$map = [
			Crm\Item::FIELD_NAME_OBSERVERS => 'OBSERVER_IDS',
			Crm\Item::FIELD_NAME_PRODUCTS => 'PRODUCT_IDS',
		];

		if ($fieldId === Crm\Item::FIELD_NAME_CONTACTS)
		{
			return 'CONTACT_IDS';
		}
		if ($convertTo === self::CONVERT_TO_DOCUMENT)
		{
			$map = array_flip($map);
		}

		return array_key_exists($fieldId, $map) ? $map[$fieldId] : $fieldId;
	}

	protected static function getFieldName(Crm\Service\Factory $factory, string $fieldId): string
	{
		$internalFieldIds = [Crm\Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY];
		try
		{
			$fieldName = $factory->getFieldCaption($fieldId);
		}
		catch (ArgumentException $exception)
		{
			$fieldName = $fieldId;
		}

		if ($fieldName === $fieldId || in_array($fieldId, $internalFieldIds, true))
		{
			$fieldName = Loc::getMessage("CRM_BP_DOCUMENT_ITEM_FIELD_{$fieldId}") ?: $fieldName;
		}

		return $fieldName;
	}

	protected static function resolveBPType(string $type): string
	{
		switch ($type)
		{
			case 'integer':
			case 'crm_product_row':
			case 'location':
				return FieldType::INT;

			case 'boolean':
				return FieldType::BOOL;

			case 'crm':
			case 'money':
			case 'url':
			case 'address':
			case 'resourcebooking':
			case 'iblock_section':
			case 'iblock_element':
				return 'UF:' . $type;

			case 'crm_deal':
			case 'crm_company':
			case 'crm_contact':
			case 'crm_entity':
			case 'crm_lead':
				return 'UF:crm';

			case 'enumeration':
			case 'crm_status':
			case 'crm_currency':
			case 'crm_category':
				return FieldType::SELECT;

			case 'employee':
				return FieldType::USER;

			default:
				return $type;
		}
	}

	protected static function getFieldOptions(array $field, Crm\Service\Factory $factory): ?array
	{
		if (array_key_exists('CLASS', $field))
		{
			switch ($field['CLASS'])
			{
				case Crm\Field\Category::class:
					$categories = [];
					foreach (static::getCategories($factory) as $category)
					{
						$categories[$category->getId()] = $category->getName();
					}

					return $categories;

				case Crm\Field\PreviousStageId::class:
				case Crm\Field\Stage::class:
					$stages = [];
					$categories = static::getCategories($factory) ?: [null];
					foreach ($categories as $category)
					{
						foreach (static::getStages($factory, $category) as $stage)
						{
							$stagePrefix = isset($category) ? $category->getName() . '/' : '';
							$stages[$stage['STATUS_ID']] = $stagePrefix . $stage['NAME'];
						}
					}

					return $stages;
			}
		}

		switch ($field['TYPE'])
		{
			case 'crm_contact':
				return ['CONTACT' => 'Y'];

			case 'crm_company':
				return ['COMPANY' => 'Y'];

			case 'crm_deal':
			case 'crm_entity':
			case 'crm_lead':
				if (isset($field['SETTINGS'], $field['SETTINGS']['parentEntityTypeId']))
				{
					$entityType = \CCrmOwnerType::ResolveName($field['SETTINGS']['parentEntityTypeId']);

					return [$entityType => 'Y'];
				}

				return null;

			case 'crm_status':
				return
					array_key_exists('CRM_STATUS_TYPE', $field)
						? \CCrmStatus::GetStatusList($field['CRM_STATUS_TYPE'])
						: []
				;

			case 'crm_currency':
				return \CCrmCurrencyHelper::PrepareListItems();

			default:
				return null;
		}
	}

	protected static function getFieldSettings(array $field, Crm\Service\Factory $factory): ?array
	{
		if (array_key_exists('CLASS', $field))
		{
			switch ($field['CLASS'])
			{
				case Crm\Field\Stage::class:
					$settings = ['Groups' => []];
					foreach (static::getCategories($factory) as $category)
					{
						$stages = [];
						foreach (static::getStages($factory, $category) as $stage)
						{
							$stages[$stage['STATUS_ID']] = $stage['NAME'];
						}

						$settings['Groups'][] = [
							'name' => $category->getName(),
							'category_id' => $category->getId(),
							'items' => $stages,
						];
					}

					return $settings;
			}
		}

		return null;
	}

	protected static function getCategories(Crm\Service\Factory $factory): array
	{
		if ($factory->isCategoriesSupported())
		{
			return $factory->getCategories();
		}
		else
		{
			return [];
		}
	}

	protected static function getStages(Crm\Service\Factory $factory, ?Category $category): Crm\EO_Status_Collection
	{
		return $factory->getStages(isset($category) ? $category->getId() : null);
	}

	protected static function fitEntityFieldsToInterface(array& $entityFields): void
	{
		if (array_key_exists(Crm\Item::FIELD_NAME_CATEGORY_ID, $entityFields))
		{
			$entityFields[Crm\Item::FIELD_NAME_CATEGORY_ID]['Type'] = FieldType::SELECT;
		}
	}
}

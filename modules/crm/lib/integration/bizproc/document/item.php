<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Crm;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Service\Container;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

if (!Loader::includeModule('bizproc'))
{
	return;
}

class Item extends \CCrmDocument implements \IBPWorkflowDocument
{
	protected const CONVERT_TO_BP = 0;
	protected const CONVERT_TO_DOCUMENT = 1;

	public static function getDocumentType($documentId): string
	{
		return static::GetDocumentInfo($documentId)['TYPE'];
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
			&& !array_key_exists('DocumentCategoryId', $arParameters)
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

	public static function CreateDocument($parentDocumentId, $fields)
	{
		$entityTypeId = static::GetDocumentInfo($parentDocumentId)['TYPE_ID'];

		$factory = Container::getInstance()->getFactory($entityTypeId);
		$newItem = $factory->createItem([]);

		$documentFieldsMap = static::getEntityFields($entityTypeId);
		$compatibleFields = [];

		foreach ($fields as $fieldId => $fieldValue)
		{
			if (array_key_exists($fieldId, $documentFieldsMap))
			{
				$field = $documentFieldsMap[$fieldId];

				$documentFieldId = static::convertFieldId($fieldId, self::CONVERT_TO_DOCUMENT);

				$documentFieldValue = static::convertToDocumentValue(
					$factory,
					[
						'fieldId' => $fieldId,
						'Description' => $field,
						'bpValue' => $fieldValue,
					],
					$newItem
				);

				if ($newItem->hasField($documentFieldId))
				{
					$newItem->set($documentFieldId, $documentFieldValue);
				}
				$compatibleFields[$documentFieldId] = $documentFieldValue;
			}
		}

		$newItem->setFromCompatibleData($compatibleFields);
		$addOperation = $factory->getAddOperation($newItem, static::getContext());

		$result = static::launchOperation($addOperation);
		$errorMessages = $result->getErrorMessages();

		return $result->isSuccess() ? $result->getId() : end($errorMessages);
	}

	public static function UpdateDocument($documentId, $fields)
	{
		$documentInfo = static::GetDocumentInfo($documentId);
		if (!$documentInfo)
		{
			throw new ArgumentNullException('documentId');
		}
		[$entityTypeId, $entityId] = [$documentInfo['TYPE_ID'], $documentInfo['ID']];

		$factory = Container::getInstance()->getFactory($entityTypeId);
		$item = isset($factory) ? $factory->getItem($entityId) : null;

		$fieldsMap = static::getEntityFields($entityTypeId);

		$compatibleFields = [];
		foreach ($fields as $fieldId => $fieldValue)
		{
			if (!array_key_exists($fieldId, $fieldsMap))
			{
				continue;
			}

			$field = $fieldsMap[$fieldId];

			$documentFieldValue = static::convertToDocumentValue(
				$factory,
				[
					'fieldId' => $fieldId,
					'Description' => $field,
					'bpValue' => $fieldValue,
				],
				$item
			);

			$documentFieldId = static::convertFieldId($fieldId, static::CONVERT_TO_DOCUMENT);
			if ($item->hasField($documentFieldId))
			{
				$item->set($documentFieldId, $documentFieldValue);
			}
			else
			{
				$compatibleFields[$documentFieldId] = $documentFieldValue;
			}
		}

		$item->setFromCompatibleData($compatibleFields);

		$updateOperation = $factory->getUpdateOperation($item, static::getContext());

		$result = static::launchOperation($updateOperation);
		$errorMessages = $result->getErrorMessages();

		return $result->isSuccess() ?: end($errorMessages);
	}

	protected static function convertToDocumentValue(
		Crm\Service\Factory $factory,
		array $fieldInfo,
		Crm\Item $item
	)
	{
		if (static::isUserField($fieldInfo['fieldId']) && $fieldInfo['Description']['Type'] === FieldType::SELECT)
		{
			$documentValue = [$fieldInfo['fieldId'] => $fieldInfo['bpValue']];
			static::InternalizeEnumerationField(
				$factory->getUserFieldEntityId(),
				$documentValue,
				$fieldInfo['fieldId']
			);

			return $documentValue[$fieldInfo['fieldId']];
		}

		if (is_array($fieldInfo['bpValue']))
		{
			$converter = function ($value) use ($factory, $fieldInfo, $item)
			{
				$fieldInfo['bpValue'] = $value;
				return static::convertToDocumentValue($factory, $fieldInfo, $item);
			};

			return
				$fieldInfo['Description']['Multiple']
					? array_map($converter, $fieldInfo['bpValue'])
					: $converter($fieldInfo['bpValue'])
			;
		}

		switch ($fieldInfo['Description']['Type'])
		{
			case FieldType::BOOL:
				return \CBPHelper::getBool($fieldInfo['bpValue']);

			case FieldType::USER:
				$documentId = \CCrmBizProcHelper::ResolveDocumentId($item->getEntityTypeId(), $item->getId());
				return
					mb_substr($fieldInfo['bpValue'], 0, mb_strlen('user_')) === 'user_'
						? (int)mb_substr($fieldInfo['bpValue'], mb_strlen('user_'))
						: static::GetUsersFromUserGroup($fieldInfo['bpValue'], $documentId[2])
				;

			case FieldType::FILE:
				$file = false;
				\CCrmFileProxy::TryResolveFile($fieldInfo['bpValue'], $file, ['ENABLE_ID' => true]);

				return $file;

			default:
				return $fieldInfo['bpValue'];
		}
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
		$deleteOperation = $factory->getDeleteOperation($factory->getItem($entityId), static::getContext());

		return static::launchOperation($deleteOperation->disableBizProc())->isSuccess();
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

	protected static function getContext(): Crm\Service\Context
	{
		$context = Container::getInstance()->getContext();
		$context->setUserId(0);
		$context->setScope(Crm\Service\Context::SCOPE_AUTOMATION);

		return $context;
	}

	public static function getDocumentName($documentId)
	{
		$documentInfo = static::GetDocumentInfo($documentId);

		$factory = Container::getInstance()->getFactory($documentInfo['TYPE_ID']);
		$item = isset($factory) ? $factory->getItem($documentInfo['ID']) : null;

		return isset($item) ? $item->getTitle() : '';
	}

	public static function normalizeDocumentId($documentId)
	{
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

	public static function getEntityFields($entityTypeId)
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		$entityFields = [];

		foreach ($factory->getFieldsInfo() as $fieldId => $field)
		{
			if (!isset($field['TYPE']))
			{
				continue;
			}

			$editable =
				!\CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::ReadOnly)
				&& !\CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::Immutable)
			;

			$required = \CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::Required);
			$multiple = \CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::Multiple);

			$entityFields[static::convertFieldId($fieldId)] = [
				'Name' => static::getFieldName($factory, $fieldId),
				'Type' => static::resolveBPType($field['TYPE']),
				'Options' => static::getFieldOptions($field, $factory),
				'Settings' => static::getFieldSettings($field, $factory),
				'Editable' => $editable,
				'Required' => $required,
				'Multiple' => $multiple,
			];
		}

		$entityFields += static::getAssignedByFields();
		$entityFields += static::getCommunicationFields();

		$CCrmUserType = new \CCrmUserType(Application::getUserTypeManager(), $factory->getUserFieldEntityId());
		$CCrmUserType->AddBPFields(
			$entityFields,
			['PRINTABLE_SUFFIX' => Loc::getMessage('CRM_FIELD_BP_TEXT')]
		);

		$entityFields += static::getUtmFields();

		$entityFields += static::getSiteFormFields($factory->getEntityTypeId());

		return $entityFields;
	}

	protected static function convertFieldId(string $fieldId, int $convertTo = self::CONVERT_TO_BP): string
	{
		$map = [Crm\Item::FIELD_NAME_OBSERVERS => 'OBSERVER_IDS'];

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
		try
		{
			$fieldName = $factory->getFieldCaption($fieldId);
		}
		catch (ArgumentException $exception)
		{
			$fieldName = $fieldId;
		}

		if ($fieldName === $fieldId)
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

			case 'crm_company':
			case 'crm_contact':
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
}

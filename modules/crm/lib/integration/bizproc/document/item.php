<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Crm;
use Bitrix\Crm\Service\Container;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
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

	public static function CreateDocument($parentDocumentId, $fields)
	{
		$entityTypeId = static::GetDocumentInfo($parentDocumentId)['TYPE_ID'];

		$factory = Container::getInstance()->getFactory($entityTypeId);

		$documentFieldsMap = static::getEntityFields($entityTypeId);
		$documentFields = [];

		foreach ($fields as $fieldId => $fieldValue)
		{
			if (array_key_exists($fieldId, $documentFields))
			{
				$userFieldEntityId = $factory->getUserFieldEntityId();
				$field = $documentFieldsMap[$fieldId];

				$documentFieldId = static::convertFieldId($field, self::CONVERT_TO_DOCUMENT);

				$documentFields[$documentFieldId] = static::convertToDocumentValue(
					$userFieldEntityId,
					$fieldId,
					$field,
					$fieldValue
				);
			}
		}

		$addOperation = $factory->getAddOperation($factory->createItem($documentFields));

		$result = static::launchOperation($addOperation);
		$errorMessages = $result->getErrorMessages();

		return $result->isSuccess() ? $result->getData()['ID'] : end($errorMessages);
	}

	public static function UpdateDocument($documentId, $fields)
	{
		$documentInfo = static::GetDocumentInfo($documentId);
		[$entityTypeId, $entityId] = [$documentInfo['TYPE_ID'], $documentInfo['ID']];

		$factory = Container::getInstance()->getFactory($entityTypeId);
		$item = $factory->getItem($entityId);

		$fieldsMap = static::getEntityFields($entityTypeId);

		foreach ($fields as $fieldId => $fieldValue)
		{
			if (!array_key_exists($fieldId, $fieldsMap))
			{
				continue;
			}

			$field = $fieldsMap[$fieldId];
			$userFieldEntityId = $factory->getUserFieldEntityId();

			$documentFieldValue = static::convertToDocumentValue($userFieldEntityId, $fieldId, $field, $fieldValue);

			$documentFieldId = static::convertFieldId($fieldId, self::CONVERT_TO_DOCUMENT);
			if ($item->hasField($documentFieldId))
			{
				$item->set($documentFieldId, $documentFieldValue);
			}
		}

		$updateOperation = $factory->getUpdateOperation($item);
		$updateOperation->getContext()->setScope(Crm\Service\Context::SCOPE_AUTOMATION);

		$result = static::launchOperation($updateOperation);
		$errorMessages = $result->getErrorMessages();

		return $result->isSuccess() ?: end($errorMessages);
	}

	protected static function convertToDocumentValue(
		string $userFieldEntityId,
		string $fieldId,
		array $bpField,
		$bpFieldValue
	)
	{
		if (static::isUserField($fieldId) && $bpField['Type'] === FieldType::SELECT)
		{
			$documentValue = [$fieldId => $bpFieldValue];
			static::InternalizeEnumerationField($userFieldEntityId, $documentValue, $fieldId);
			return $documentValue[$fieldId];
		}

		if (is_array($bpFieldValue))
		{
			$converter = function ($value) use ($userFieldEntityId, $fieldId, $bpField)
			{
				return static::convertToDocumentValue($userFieldEntityId, $fieldId, $bpField, $value);
			};

			return
				$bpField['Multiple']
					? array_map($converter, $bpFieldValue)
					: $converter($bpFieldValue)
			;
		}

		switch ($bpField['Type'])
		{
			case FieldType::BOOL:
				return \CBPHelper::getBool($bpFieldValue);

			case FieldType::USER:
				return (int)mb_substr($bpFieldValue, mb_strlen('user_'));

			case FieldType::FILE:
				$file = false;
				\CCrmFileProxy::TryResolveFile($bpFieldValue, $file, ['ENABLE_ID' => true]);

				return $file;

			default:
				return $bpFieldValue;
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
		$deleteOperation = $factory->getDeleteOperation($factory->getItem($entityId));

		return static::launchOperation($deleteOperation)->isSuccess();
	}

	protected static function launchOperation(Operation $operation): Result
	{
		$dbConnection = Application::getConnection();
		if (static::shouldUseTransaction())
		{
			$dbConnection->startTransaction();
		}

		$operationResult = $operation->launch();

		if (static::shouldUseTransaction())
		{
			$operationResult->isSuccess() ? $dbConnection->commitTransaction() : $dbConnection->rollbackTransaction();
		}

		return $operationResult;
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
				'Options' => static::getFieldOptionsByType($field['TYPE']),
				'Editable' => $editable,
				'Required' => $required,
				'Multiple' => $multiple,
			];
		}

		$entityFields += static::getAssignedByFields();
		$entityFields += static::getCommunicationFields();

		$CCrmUserType = new \CCrmUserType(Application::getUserTypeManager(), $factory->getUserFieldEntityId());
		$CCrmUserType->AddBPFields($entityFields, ['PRINTABLE_SUFFIX' => Loc::getMessage('CRM_FIELD_BP_TEXT')]);

		$entityFields += static::getUtmFields();

		$entityFields += static::getSiteFormFields($factory->getEntityTypeId());

		return $entityFields;
	}

	protected static function convertFieldId(string $fieldId, int $convertTo = self::CONVERT_TO_BP): string
	{
		$map = ['OBSERVERS' => 'OBSERVER_IDS'];

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
			case 'crm_status':
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
			case 'crm_currency':
				return FieldType::SELECT;

			case 'employee':
				return FieldType::USER;

			default:
				return $type;
		}
	}

	protected static function getFieldOptionsByType(string $type): ?array
	{
		switch ($type)
		{
			case 'crm_contact':
				return ['CONTACT' => 'Y'];
			case 'crm_company':
				return ['COMPANY' => 'Y'];
			case 'crm_currency':
				return \CCrmCurrencyHelper::PrepareListItems();
			default:
				return null;
		}
	}
}

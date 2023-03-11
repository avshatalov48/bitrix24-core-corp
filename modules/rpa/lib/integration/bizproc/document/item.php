<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Bitrix\Rpa\Integration\Bizproc\Document;

use Bitrix\Main;
use Bitrix\Rpa;
use Bitrix\Bizproc;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Integration\Bizproc\Automation\Factory;
use Bitrix\Rpa\Model\ItemHistoryTable;
use Bitrix\Rpa\Model\TypeTable;

if (!Main\Loader::includeModule('bizproc'))
{
	return;
}

class Item implements \IBPWorkflowDocument
{
	protected const GROUP_RESPONSIBLE_HEAD = 'responsible_head';
	private const BP_USER_PREFIX = 'user_';

	public static function getDocumentType($documentId): string
	{
		return 'T'.explode(':', $documentId)[0];
	}

	public static function getDocumentFieldTypes($documentType): array
	{
		global $USER_FIELD_MANAGER;

		$result = \CBPHelper::GetDocumentFieldTypes();

		$userTypes = $USER_FIELD_MANAGER->GetUserType();
		foreach ($userTypes as $userType)
		{
			$bpType = self::resolveUserFieldType($userType['USER_TYPE_ID']);
			if ($bpType && mb_strpos($bpType, 'UF:') === 0)
			{
				switch ($bpType)
				{
					case 'UF:money':
						$typeClass = Bizproc\UserType\Money::class;
						break;
					case 'UF:iblock_element':
						$typeClass = Bizproc\UserType\IblockElement::class;
						break;
					case 'UF:iblock_section':
						$typeClass = Bizproc\UserType\IblockSection::class;
						break;
					default:
						$typeClass = Bizproc\UserType\UserFieldBase::class;
				}

				$result[$bpType] = [
					'Name' => $userType['DESCRIPTION'],
					'BaseType' => $userType['BASE_TYPE'],
					'typeClass' => $typeClass,
				];
			}
		}

		return $result;
	}

	public static function canUserOperateDocument($operation, $userId, $documentId, $arParameters = []): bool
	{
		$userId = (int) $userId;
		$user = new \CBPWorkflowTemplateUser($userId);

		if ($user->isAdmin())
		{
			return true; //Admin is the Lord of the Automation
		}

		switch ($operation)
		{
			case \CBPCanUserOperateOperation::CreateWorkflow:
			case \CBPCanUserOperateOperation::CreateAutomation:
			{
				$typeId = static::getDocumentTypeId($documentId);
				return Driver::getInstance()->getUserPermissions()->canModifyType($typeId);
				break;
			}

			case \CBPCanUserOperateOperation::StartWorkflow:
			case \CBPCanUserOperateOperation::ViewWorkflow:
			case \CBPCanUserOperateOperation::ReadDocument:
			case \CBPCanUserOperateOperation::WriteDocument:
			{
				//check permissions
				break;
			}
		}

		return false;
	}

	public static function canUserOperateDocumentType($operation, $userId, $documentType, $arParameters = []): bool
	{
		$userId = (int) $userId;
		$user = new \CBPWorkflowTemplateUser($userId);
		$typeId = (int) str_replace('T', '', $documentType);

		if ($user->isAdmin())
		{
			return true; //Admin is the Lord of the Automation
		}

		if ($operation === \CBPCanUserOperateOperation::CreateAutomation)
		{
			return Driver::getInstance()->getUserPermissions()->canModifyType($typeId);
		}

		return false;
	}

	public static function getDocumentAdminPage($documentId): string
	{
		[$typeId, $itemId] = explode(':', $documentId);
		return "/rpa/item/{$typeId}/{$itemId}/";
	}

	public static function getDocumentFields($documentType): array
	{
		$stages = static::getDocumentStages($documentType);

		$fields = [
			'ID' => [
				'Name' => 'ID',
				'Type' => 'int',
			],
			'STAGE_ID' => [
				'Name' => Loc::getMessage('RPA_BP_DOCUMENT_ITEM_STAGE_ID'),
				'Type' => 'select',
				'Options' => $stages,
			],
			'PREVIOUS_STAGE_ID' => [
				'Name' => Loc::getMessage('RPA_BP_DOCUMENT_ITEM_PREVIOUS_STAGE_ID'),
				'Type' => 'select',
				'Options' => $stages,
			],
			'XML_ID' => [
				'Name' => Loc::getMessage('RPA_BP_DOCUMENT_ITEM_XML_ID'),
				'Type' => 'string',
				'Editable' => true,
			],
			'CREATED_BY' => [
				'Name' => Loc::getMessage('RPA_BP_DOCUMENT_ITEM_CREATED_BY'),
				'Type' => 'user',
			],
			'UPDATED_BY' => [
				'Name' => Loc::getMessage('RPA_BP_DOCUMENT_ITEM_UPDATED_BY'),
				'Type' => 'user',
			],
			'MOVED_BY' => [
				'Name' => Loc::getMessage('RPA_BP_DOCUMENT_ITEM_MOVED_BY'),
				'Type' => 'user',
			],
			'CREATED_TIME' => [
				'Name' => Loc::getMessage('RPA_BP_DOCUMENT_ITEM_CREATED_TIME'),
				'Type' => 'datetime',
			],
			'UPDATED_TIME' => [
				'Name' => Loc::getMessage('RPA_BP_DOCUMENT_ITEM_UPDATED_TIME'),
				'Type' => 'datetime',
			],
			'MOVED_TIME' => [
				'Name' => Loc::getMessage('RPA_BP_DOCUMENT_ITEM_MOVED_TIME'),
				'Type' => 'datetime',
			],
		];

		$type = self::getType($documentType);
		if ($type)
		{
			foreach($type->getUserFieldCollection() as $field)
			{
				$fieldType = self::resolveUserFieldType($field->getUserTypeId());
				if (!$fieldType)
				{
					continue;
				}

				$fields[$field->getName()] = self::createPropertyFromUserField($fieldType, $field);
			}
		}

		return $fields;
	}

	public static function getDocument($documentId, $documentType = null): ?array
	{
		$fields = null;
		[$typeId, $itemId] = explode(':', $documentId);

		$type = TypeTable::getById($typeId)->fetchObject();

		if ($type && $item = $type->getItem($itemId))
		{
			$fields = $item->collectValues();
			self::internalizeFieldsValues($documentId, $fields);
		}

		return $fields;
	}

	private static function internalizeFieldsValues($documentId, array &$fields)
	{
		$docFields = static::getDocumentFields(static::getDocumentType($documentId));

		foreach ($docFields as $id => $field)
		{
			if (!isset($fields[$id]))
			{
				continue;
			}

			if ($field['Type'] === 'user')
			{
				$fields[$id] = self::addUserPrefix($fields[$id]);
			}

			if ($field['Type'] === 'select' && isset($field['Settings']['ENUM']))
			{
				$fields[$id] = self::convertSelectValues($fields[$id], $field['Settings']['ENUM']);
			}
		}
	}

	private static function addUserPrefix($value)
	{
		if (is_scalar($value))
		{
			$value = $value > 0 ? self::BP_USER_PREFIX.$value : null;
		}
		if (is_array($value))
		{
			foreach ($value as $i => $item)
			{
				$value[$i] = $item > 0 ? self::BP_USER_PREFIX.$item : null;
			}
			$value = array_filter($value);
		}

		return $value;
	}

	private static function convertSelectValues($value, $enums)
	{
		$map = [];
		foreach ($enums as $enum)
		{
			$map[(int) $enum['ID']] = $enum['XML_ID'];
		}

		if (is_array($value))
		{
			foreach ($value as $i => $val)
			{
				$value[$i] = $map[$val];
			}

			$value = array_values($value);
		}
		else
		{
			$value = $map[$value];
		}

		return $value;
	}

	public static function createDocument($parentDocumentId, $arFields)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function updateDocument($documentId, $fields, $modifiedById = null)
	{
		[$typeId, $itemId] = explode(':', $documentId);

		$type = TypeTable::getById($typeId)->fetchObject();

		if (!$type)
		{
			return false;
		}

		$item = $type->getItem($itemId);

		if(!$item)
		{
			return false;
		}

		$taskId = $fields['__taskId'] ?? 0;

		if (isset($fields['STAGE_ID']) && empty($fields['STAGE_ID']))
		{
			unset($fields['STAGE_ID']);
		}

		//TODO: prepare white list, values etc.
		foreach($fields as $name => $value)
		{
			if($item->entity->hasField($name))
			{
				$item->set($name, $value);
			}
		}

		$command = Driver::getInstance()->getFactory()->getUpdateCommand($item);

		$command->setScope(ItemHistoryTable::SCOPE_AUTOMATION);
		$command->disableAllChecks();

		$command->setUserId((int)$modifiedById);

		if ($taskId)
		{
			$command->setTaskId($taskId);
			$command->setScope(ItemHistoryTable::SCOPE_TASK);
		}

		$result = $command->run();

		return $result->isSuccess();
	}

	public static function deleteDocument($documentId)
	{
		[$typeId, $itemId] = explode(':', $documentId);

		$type = TypeTable::getById($typeId)->fetchObject();

		if (!$type)
		{
			return false;
		}

		$item = $type->getItem($itemId);

		if(!$item)
		{
			return false;
		}

		$command = Driver::getInstance()->getFactory()->getDeleteCommand($item);

		$command->setScope(ItemHistoryTable::SCOPE_AUTOMATION);
		$command->disableCheckAccess();
		$command->setUserId(0);

		$result = $command->run();

		return $result->isSuccess();
	}

	public static function getEntityName($entity): string
	{
		return Loc::getMessage('RPA_BP_ITEM_ENTITY_NAME');
	}

	public static function getDocumentName($documentId): string
	{
		[$typeId, $itemId] = explode(':', $documentId);
		return "#{$itemId}";
	}

	public static function getDocumentTypeId($documentId): int
	{
		[$typeId, $itemId] = explode(':', $documentId);

		return (int) $typeId;
	}

	public static function getDocumentItemId(string $documentId): int
	{
		[$typeId, $itemId] = explode(':', $documentId);

		return (int) $itemId;
	}

	public static function getDocumentTypeName($documentType): string
	{
		$type = static::getType($documentType);
		if (!$type)
		{
			return '';
		}

		return $type['TITLE'];
	}

	public static function createAutomationTarget($documentType)
	{
		return Factory::createTarget(static::makeComplexType($documentType));
	}

	public static function makeComplexId(int $typeId, int $itemId): array
	{
		return [Driver::MODULE_ID, __CLASS__, "{$typeId}:{$itemId}"];
	}

	public static function makeComplexType($typeId): array
	{
		if (is_numeric($typeId))
		{
			$typeId = 'T'.$typeId;
		}

		return [Driver::MODULE_ID, __CLASS__, $typeId];
	}

	public static function getAllowableOperations($documentType): array
	{
		return [];
	}

	public static function getAllowableUserGroups($documentType): array
	{
		return [
			static::GROUP_RESPONSIBLE_HEAD => Loc::getMessage('RPA_BP_DOCUMENT_ITEM_USER_GROUP_HEAD'),
		];
	}

	public static function getUsersFromUserGroup($group, $documentId): array
	{
		if ($group === static::GROUP_RESPONSIBLE_HEAD)
		{
			$createdBy = static::getDocumentResponsible($documentId);
			$createdById = \CBPHelper::StripUserPrefix($createdBy);
			$userService = \CBPRuntime::GetRuntime()->getUserService();

			return $userService->getUserHeads($createdById);
		}

		return [];
	}

	public static function getDocumentResponsible($documentId)
	{
		//TODO: make some perf optimization
		return static::getDocument($documentId)['CREATED_BY'];
	}

	public static function isFeatureEnabled($documentType, $feature): bool
	{
		return in_array($feature, [\CBPDocumentService::FEATURE_SET_MODIFIED_BY]);
	}

	public static function onTaskChange(string $documentId, $taskId, array $taskData, $status): void
	{
		$result = [];
		[$typeId, $itemId] = explode(':', $documentId);

		$incremented = (isset($taskData['COUNTERS_INCREMENTED']) && is_array($taskData['COUNTERS_INCREMENTED'])) ? $taskData['COUNTERS_INCREMENTED'] : [];
		$decremented = (isset($taskData['COUNTERS_DECREMENTED']) && is_array($taskData['COUNTERS_DECREMENTED'])) ? $taskData['COUNTERS_DECREMENTED'] : [];
		$userIds = array_merge($incremented, $decremented);
		foreach($userIds as $userId)
		{
			$isIncremented = in_array($userId, $incremented, true);
			$isDecremented = in_array($userId, $decremented, true);
			if($isIncremented && $isDecremented)
			{
				continue;
			}

			if($isIncremented)
			{
				$result[$userId] = '+1';
				\CUserCounter::Increment($userId, 'rpa_tasks', '**');
			}
			elseif($isDecremented)
			{
				$result[$userId] = '-1';
				\CUserCounter::Decrement($userId, 'rpa_tasks', '**');
			}
		}

		if(!empty($result))
		{
			Driver::getInstance()->getPullManager()->sendTaskCountersEvent($typeId, $itemId, $result);
		}
	}

	public static function getDocumentStages(string $documentType): array
	{
		$type = self::getType($documentType);
		$stages = [];

		if ($type)
		{
			foreach($type->getStages() as $stage)
			{
				$stages[$stage->getId()] = $stage->getName();
			}
		}

		return $stages;
	}

	private static function getType(string $documentType): ?Rpa\Model\Type
	{
		$typeId = (int) str_replace('T', '', $documentType);
		return $typeId ? Rpa\Model\TypeTable::getById($typeId)->fetchObject() : null;
	}

	private static function resolveUserFieldType(string $type): ?string
	{
		$bpType = null;
		switch ($type)
		{
			case 'string':
			case 'datetime':
			case 'date':
			case 'double':
			case 'file':
				$bpType = $type;
				break;
			case 'integer':
				$bpType = 'int';
				break;
			case 'boolean':
				$bpType = 'bool';
				break;
			case 'employee':
				$bpType = 'user';
				break;
			case 'enumeration':
				$bpType = 'select';
				break;
			case 'money':
			case 'url':
			case 'address':
			case 'resourcebooking':
			case 'crm_status':
			case 'iblock_section':
			case 'iblock_element':
			case 'crm':
				$bpType = "UF:{$type}";
				break;
		}
		return $bpType;
	}

	private static function createPropertyFromUserField(string $fieldType, \Bitrix\Rpa\UserField\UserField $field): array
	{
		$property = [
			'Name' => $field->getTitle(),
			'Editable' => $field->isEditable(),
			'Type' => $fieldType,
			'Required' => $field->isMandatory(),
			'Multiple' => $field->isMultiple(),
			'Settings' => $field->getSettings(),
		];

		if ($fieldType === 'select')
		{
			$data = $field->toArray();
			$options = [];
			if (isset($data['ENUM']))
			{
				$map = [];
				foreach ($data['ENUM'] as $enum)
				{
					$options[$enum['XML_ID']] = $enum['VALUE'];
					$map[$enum['XML_ID']] = $enum['ID'];
				}
				$property['Settings']['ExternalValues'] = $map;
				$property['Settings']['ENUM'] = $data['ENUM'];
			}
			$property['Options'] = $options;
		}
		elseif ($fieldType === 'bool')
		{
			$property['Settings']['ExternalValues'] = ['N' => 0, 'Y' => 1];
		}
		elseif ($fieldType === 'user')
		{
			$property['Settings']['ExternalExtract'] = true;
		}

		return $property;
	}

	// Old & deprecated below
	public static function GetJSFunctionsForFields()
	{
		return '';
	}

	public static function publishDocument($documentId)
	{
		return true;
	}

	public static function unpublishDocument($documentId)
	{
		return true;
	}

	public static function lockDocument($documentId, $workflowId)
	{
		return true;
	}

	public static function unlockDocument($documentId, $workflowId)
	{
		return true;
	}

	public static function isDocumentLocked($documentId, $workflowId)
	{
		return false;
	}

	public static function getDocumentForHistory($documentId, $historyIndex)
	{
		return [];
	}

	public static function recoverDocumentFromHistory($documentId, $arDocument)
	{
		return true;
	}
}
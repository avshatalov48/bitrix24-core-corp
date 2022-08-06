<?php

namespace Bitrix\Crm\Controller\Duplicate;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integrity\Volatile\FieldInfo;
use Bitrix\Crm\Integrity\Volatile\Type\State;
use Bitrix\Crm\Integrity\Volatile\TypeInfo;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;

class VolatileType extends \Bitrix\Main\Engine\Controller
{
	public function fieldsAction(int $entityTypeId = null): ?array
	{
		if (!$this->checkPermissions())
		{
			return null;
		}
		$fields = FieldInfo::getInstance()->getFieldInfo($entityTypeId ? [$entityTypeId] : []);
		$result = [];
		foreach ($fields as $entityTypeId => $entityFields)
		{
			foreach ($entityFields as $fieldPath => $fieldParams)
			{
				$result[] = [
					'entityTypeId' => $entityTypeId,
					'fieldCode' => $fieldPath,
					'fieldTitle' => $fieldParams['title'],
				];
			}
		}

		return $result;
	}

	public function listAction(): ?array
	{
		if (!$this->checkPermissions())
		{
			return null;
		}

		$types = TypeInfo::getInstance()->get();
		$fieldInfoInstance = FieldInfo::getInstance();

		$result = [];
		foreach ($types as $type)
		{
			if ($type['STATE_ID'] === State::STATE_FREE)
			{
				continue;
			}

			$result[] = [
				'id' => $type['ID'],
				'entityTypeId' => $type['ENTITY_TYPE_ID'],
				'fieldCode' => $fieldInfoInstance->getPathName($type['FIELD_PATH'], $type['FIELD_NAME']),
			];
		}

		return $result;
	}

	public function registerAction(int $entityTypeId, string $fieldCode): ?array
	{
		if (!$this->checkPermissions())
		{
			return null;
		}

		$fieldInfoInstance = FieldInfo::getInstance();
		$availableFields = $fieldInfoInstance->getFieldInfo([$entityTypeId]);
		if (!isset($availableFields[$entityTypeId][$fieldCode]))
		{
			$this->addError(new Error(
				'Field not found',
				'FIELD_NOT_FOUND'
			));

			return null;
		}

		['path' => $fieldPath, 'name' => $fieldName] = $fieldInfoInstance->splitFieldPath($fieldCode);

		$typeInfoInstance = TypeInfo::getInstance();
		$types = $typeInfoInstance->get();

		foreach ($types as $type)
		{
			// field is already bound to a volatile type:
			if (
				$type['ENTITY_TYPE_ID'] == $entityTypeId
				&& $type['FIELD_PATH'] == $fieldPath
				&& $type['FIELD_NAME'] == $fieldName
				&& $type['STATE_ID'] != State::STATE_FREE
			)
			{
				return [
					'id' => $type['ID'],
				];
			}
		}

		$typeId = null;
		$possibleTypesIds = \Bitrix\Crm\Integrity\DuplicateVolatileCriterion::getSupportedDedupeTypes();
		foreach ($possibleTypesIds as $possibleTypeId)
		{
			if (!isset($types[$possibleTypeId]) || $types[$possibleTypeId]['STATE_ID'] == State::STATE_FREE)
			{
				$typeId = $possibleTypeId;
				break;
			}
		}

		if (is_null($typeId))
		{
			$this->addError(new Error(
				'There is already a maximum number of volatile types',
				'MAX_TYPES_COUNT_EXCEEDED'
			));

			return null;
		}

		$typeInfoInstance->assign(
			$entityTypeId,
			$typeId,
			$fieldPath,
			$fieldName
		);

		return [
			'id' => $typeId,
		];
	}

	public function unregisterAction(int $id): ?bool
	{
		if (!$this->checkPermissions())
		{
			return null;
		}

		$typeInfoInstance = TypeInfo::getInstance();
		$type = $typeInfoInstance->getById($id);
		if (!$type)
		{
			$this->addError(new Error(
				'This type is not assigned',
				'TYPE_IS_NOT_ASSIGNED'
			));

			return null;
		}
		$typeInfoInstance->release($id);

		return true;
	}

	protected function checkPermissions(): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions();
		if ($userPermissions->isAdmin() || $userPermissions->canWriteConfig())
		{
			return true;
		}

		$this->addError(ErrorCode::getAccessDeniedError());

		return false;
	}
}

<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class Import extends Operation\Add
{
	public const ERROR_CODE_ITEM_IMPORT_ACCESS_DENIED = 'CRM_ITEM_IMPORT_ACCESS_DENIED';

	public const MESSAGE_FIELD_CRATED_TIME_VALUE_NOT_MONOTONE = 'CRM_FIELD_CRATED_TIME_VALUE_NOT_MONOTONE_ERROR';
	public const MESSAGE_FIELD_CRATED_TIME_VALUE_IN_FUTURE = 'CRM_FIELD_CRATED_TIME_VALUE_IN_FUTURE_ERROR';
	public const MESSAGE_FIELD_VALUE_CAN_NOT_BE_GREATER = 'CRM_FIELD_VALUE_CAN_NOT_BE_GREATER_ERROR';
	public const MESSAGE_FIELD_ONLY_ADMIN_CAN_SET = 'CRM_FIELD_VALUE_ONLY_ADMIN_CAN_SET_ERROR';

	public function checkAccess(): Result
	{
		$result = new Result();

		$userPermissions = Container::getInstance()->getUserPermissions($this->getContext()->getUserId());
		$canImportItem = $userPermissions->canImportItem($this->item);

		if (!$canImportItem)
		{
			$result->addError(
				new Error(
					Loc::getMessage('CRM_TYPE_ITEM_PERMISSIONS_IMPORT_DENIED'),
					static::ERROR_CODE_ITEM_IMPORT_ACCESS_DENIED,
				)
			);
		}

		return $result;
	}

	public function isAutomationEnabled(): bool
	{
		// automation is always disabled in import for performance reasons
		return false;
	}

	public function isBizProcEnabled(): bool
	{
		// bizproc is always disabled in import for performance reasons
		return false;
	}

	public function processFieldsBeforeSave(): Result
	{
		$systemFields = [
			Item::FIELD_NAME_CREATED_TIME,
			Item::FIELD_NAME_UPDATED_TIME,
			Item::FIELD_NAME_MOVED_TIME,
			Item::FIELD_NAME_CREATED_BY,
			Item::FIELD_NAME_UPDATED_BY,
			Item::FIELD_NAME_MOVED_BY,
		];
		$systemFieldsValues = [];
		foreach ($systemFields as $fieldName)
		{
			$fieldValue = $this->item->hasField($fieldName) ? $this->item->get($fieldName) : null;
			if (!is_null($fieldValue) && $this->isDefaultValue($fieldName, $fieldValue))
			{
				$fieldValue = null;
			}
			$systemFieldsValues[$fieldName] = $fieldValue;
		}

		$result = $this->checkSystemFieldsValues($systemFieldsValues);

		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = parent::processFieldsBeforeSave();

		foreach ($systemFieldsValues as $fieldName => $fieldValue)
		{
			if (!is_null($fieldValue))
			{
				$this->item->set($fieldName, $fieldValue);
			}
		}

		return $result;
	}

	public function checkSystemFieldsValues(array $systemFieldsValues): Result
	{
		$result = new Result();

		$canSetSystemFields = Container::getInstance()->getUserPermissions($this->getContext()->getUserId())->isAdmin();
		if ($canSetSystemFields)
		{
			$createdTime = $systemFieldsValues[Item::FIELD_NAME_CREATED_TIME];
			$updatedTime = $systemFieldsValues[Item::FIELD_NAME_UPDATED_TIME];
			$movedTime = $systemFieldsValues[Item::FIELD_NAME_MOVED_TIME];

			if ($createdTime)
			{
				$maxPossibleCreatedTime = $this->getLastAddedItemCreatedTime();
				if ($maxPossibleCreatedTime && $maxPossibleCreatedTime->getTimestamp() > $createdTime->getTimestamp())
				{
					$result->addError($this->getFieldCreatedTimeIsNotMonotoneError(Item::FIELD_NAME_CREATED_TIME));
				}
				if ($createdTime->getTimestamp() > (new DateTime())->getTimestamp())
				{
					$result->addError($this->getFieldCreatedTimeIsInFutureError(Item::FIELD_NAME_CREATED_TIME));
				}
			}

			if ($updatedTime)
			{
				$minPossibleUpdatedTime = $createdTime ?? (new DateTime());
				if ($minPossibleUpdatedTime->getTimestamp() > $updatedTime->getTimestamp())
				{
					$result->addError($this->getFieldCompareValueError(
						Item::FIELD_NAME_UPDATED_TIME,
						Item::FIELD_NAME_CREATED_TIME
					));
				}
				if ($updatedTime->getTimestamp() > (new DateTime())->getTimestamp())
				{
					$result->addError($this->getFieldCreatedTimeIsInFutureError(Item::FIELD_NAME_UPDATED_TIME));
				}
			}

			if ($movedTime)
			{
				$createdTimeValue = ($createdTime ?? (new DateTime()))->getTimestamp();
				$updatedTimeValue = $updatedTime ? $updatedTime->getTimestamp() : $createdTimeValue;
				if ($movedTime->getTimestamp() < $createdTimeValue)
				{
					$result->addError($this->getFieldCompareValueError(
						Item::FIELD_NAME_CREATED_TIME,
						Item::FIELD_NAME_MOVED_TIME,
					));
				}
				if ($movedTime->getTimestamp() > $updatedTimeValue)
				{
					$result->addError($this->getFieldCompareValueError(
						Item::FIELD_NAME_MOVED_TIME,
						Item::FIELD_NAME_UPDATED_TIME
					));
				}
			}

			foreach ([
				Item::FIELD_NAME_CREATED_BY,
				Item::FIELD_NAME_UPDATED_BY,
				Item::FIELD_NAME_MOVED_BY,
			] as $fieldName)
			{
				$fieldValue = $systemFieldsValues[$fieldName];
				if ($fieldValue && $fieldValue < 0)
				{
					$result->addError(
						$this->fieldsCollection->getField($fieldName)->getValueNotValidError()
					);
				}
			}
		}
		else
		{
			foreach ($systemFieldsValues as $fieldName => $fieldValue)
			{

				if (!is_null($fieldValue))
				{
					$result->addError($this->getOnlyAdminCanSetFieldError($fieldName));
				}
			}
		}

		return $result;
	}

	protected function getLastAddedItemCreatedTime(): ?DateTime
	{
		$factory = Container::getInstance()->getFactory($this->item->getEntityTypeId());
		$lastAddedItem = $factory->getItems([
				'limit' => 1,
				'order' => [
					Item::FIELD_NAME_ID => 'desc',
				],
				'select' => [
					Item::FIELD_NAME_ID,
					$factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_TIME),
				],
			])[0] ?? null;

		return $lastAddedItem ? ($lastAddedItem->getCreatedTime()) : null;
	}

	protected function getFieldCreatedTimeIsNotMonotoneError(string $fieldName): Error
	{
		$field = $this->fieldsCollection->getField($fieldName);
		$fieldTitle = $field->getTitle() ?? $field->getName();

		$message = Loc::getMessage(static::MESSAGE_FIELD_CRATED_TIME_VALUE_NOT_MONOTONE, [
			'#FIELD#' => $fieldTitle,
		]);

		return new Error(
			$message,
			\Bitrix\Crm\Field::ERROR_CODE_VALUE_NOT_VALID,
			[
				'fieldName' => $field->getName(),
			]
		);
	}

	protected function getFieldCreatedTimeIsInFutureError(string $fieldName): Error
	{
		$field = $this->fieldsCollection->getField($fieldName);
		$fieldTitle = $field->getTitle() ?? $field->getName();

		$message = Loc::getMessage(static::MESSAGE_FIELD_CRATED_TIME_VALUE_IN_FUTURE, [
			'#FIELD#' => $fieldTitle,
		]);

		return new Error(
			$message,
			\Bitrix\Crm\Field::ERROR_CODE_VALUE_NOT_VALID,
			[
				'fieldName' => $field->getName(),
			]
		);
	}

	protected function getFieldCompareValueError(string $fieldName1, string $fieldName2): Error
	{
		$field1 = $this->fieldsCollection->getField($fieldName1);
		$fieldTitle1 = $field1->getTitle() ?? $field1->getName();

		$field2 = $this->fieldsCollection->getField($fieldName2);
		$fieldTitle2 = $field2->getTitle() ?? $field2->getName();

		$message = Loc::getMessage(static::MESSAGE_FIELD_VALUE_CAN_NOT_BE_GREATER, [
			'#FIELD1#' => $fieldTitle1,
			'#FIELD2#' => $fieldTitle2,
		]);

		return new Error(
			$message,
			\Bitrix\Crm\Field::ERROR_CODE_VALUE_NOT_VALID,
			[
				'fieldName' => $field1->getName(),
			]
		);
	}

	protected function getOnlyAdminCanSetFieldError(string $fieldName): Error
	{
		$field = $this->fieldsCollection->getField($fieldName);
		$fieldTitle = $field->getTitle() ?? $field->getName();

		$message = Loc::getMessage(static::MESSAGE_FIELD_ONLY_ADMIN_CAN_SET, [
			'#FIELD#' => $fieldTitle,
		]);

		return new Error(
			$message,
			\Bitrix\Crm\Field::ERROR_CODE_VALUE_NOT_VALID,
			[
				'fieldName' => $field->getName(),
			]
		);
	}

	protected function isDefaultValue(string $fieldName, $fieldValue): bool
	{
		$defaultValue = $this->item->getDefaultValue($fieldName);
		if (is_scalar($fieldValue) && is_scalar($defaultValue))
		{
			return $defaultValue == $fieldValue;
		}
		if ($fieldValue instanceof Date && $defaultValue instanceof Date)
		{
			return (abs($defaultValue->getTimestamp() - $fieldValue->getTimestamp()) <= 1); // allow 1 second difference to avoid "seconds jumping"
		}
		// @todo support compare values of multiple field, if become necessary

		return false;
	}
}

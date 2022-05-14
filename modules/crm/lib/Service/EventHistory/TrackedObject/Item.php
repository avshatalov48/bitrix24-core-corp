<?php

namespace Bitrix\Crm\Service\EventHistory\TrackedObject;

use Bitrix\Crm\Comparer\MultifieldComparer;
use Bitrix\Crm\Format\Money;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EventHistory\EventHistoryData;
use Bitrix\Crm\Service\EventHistory\TrackedObject;
use Bitrix\Main\Localization\Loc;

/**
 * Class Item
 *
 * @property \Bitrix\Crm\Item $objectBeforeSave
 * @property \Bitrix\Crm\Item $object
 */
class Item extends TrackedObject
{
	protected $trackedRegularFieldNames = [];

	public function setTrackedFieldNames(array $trackedFieldNames): TrackedObject
	{
		$this->trackedRegularFieldNames = $trackedFieldNames;

		return $this;
	}

	protected function getTrackedRegularFieldNames(): array
	{
		return $this->trackedRegularFieldNames;
	}

	protected static function getEntityTitleMethod(): string
	{
		return 'getHeading';
	}

	protected function getEntityTypeId(): int
	{
		return $this->objectBeforeSave->getEntityTypeId();
	}

	protected function getUpdateEventName(string $fieldName): string
	{
		$message = Loc::getMessage('CRM_TRACKED_OBJECT_ITEM_EVENT_NAME_UPDATE_'.$fieldName);
		if (!empty($message))
		{
			return $message;
		}

		return parent::getUpdateEventName($fieldName);
	}

	protected function getEntityAddOrDeleteEventName(string $fieldName, string $addOrDelete): string
	{
		if ($addOrDelete === static::ADD)
		{
			return Loc::getMessage('CRM_TRACKED_OBJECT_ITEM_EVENT_NAME_ADD_'.$fieldName);
		}

		if ($addOrDelete === static::DELETE)
		{
			return Loc::getMessage('CRM_TRACKED_OBJECT_ITEM_EVENT_NAME_DELETE_'.$fieldName);
		}

		return '';
	}

	protected function getFieldNameCaption(string $fieldName): string
	{
		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());

		return $factory ? $factory->getFieldCaption($fieldName) : $fieldName;
	}

	protected function getFieldValueCaption(string $fieldName, $fieldValue, string $actualOrCurrent = null): string
	{
		if ($fieldName === \Bitrix\Crm\Item::FIELD_NAME_OPPORTUNITY)
		{
			if ($actualOrCurrent === static::ACTUAL)
			{
				$currencyId = $this->getActualValue(\Bitrix\Crm\Item::FIELD_NAME_CURRENCY_ID);
			}
			elseif ($actualOrCurrent === static::CURRENT)
			{
				$currencyId = $this->getCurrentValue(\Bitrix\Crm\Item::FIELD_NAME_CURRENCY_ID);
			}

			return Money::format((float)$fieldValue, $currencyId);
		}

		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());

		return $factory ? $factory->getFieldValueCaption($fieldName, $fieldValue) : (string)$fieldValue;
	}

	protected function prepareUpdateEventDataForField(string $fieldName): array
	{
		if ($fieldName === \Bitrix\Crm\Item::FIELD_NAME_FM && !$this->isDependantField($fieldName))
		{
			/** @var \Bitrix\Crm\Multifield\Collection $actual */
			$actual = $this->getActualValue($fieldName);
			/** @var \Bitrix\Crm\Multifield\Collection $current */
			$current = $this->getCurrentValue($fieldName);

			$comparer = new MultifieldComparer();

			$changes = \CCrmFieldMulti::CompareFields(
				$actual->toArray(),
				$comparer->getChangedCompatibleArray($actual, $current),
			);

			$result = [];
			foreach ($changes as $change)
			{
				$data = new EventHistoryData($change);
				$data->setEntityType($this->getEntityType());
				$data->setEntityId($this->getEntityId());

				$result[] = $data;
			}

			return $result;
		}

		return parent::prepareUpdateEventDataForField($fieldName);
	}
}

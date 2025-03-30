<?php

namespace Bitrix\Sign\Model\ItemBinder;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Text\StringHelper;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract\Item\IntModelValue;
use Bitrix\Sign\Contract\Item\TrackableItem;

/**
 * To update items using Contract/Item you should
 *
 * 1) implement TrackableItem interface in your Item using TrackableItemTrait
 * 2) use scalar, DateTime, Enum type of properties in your Item
 * 3) use camelCase properties same as SNAKE_CASE names of fields in your EntityObject model
 * (for example for field SOME_FIELD in model use $item->someField property name)
 */
class BaseItemToModelBinder
{
	public function __construct(
		private readonly TrackableItem $item,
		private readonly EntityObject $model,
	) {}

	public function setChangedItemPropertiesToModel(): void
	{
		foreach ($this->item->getOriginal() as $name => $originalValue)
		{
			$currentValue = $this->item->{$name};
			$this->setValueToModelIfNeed($currentValue, $originalValue, $name);
		}
	}

	protected function setValueToModelIfNeed(mixed $currentValue, mixed $originalValue, string $name): void
	{
		if ($this->isItemPropertyShouldSetToItem($currentValue, $originalValue, $name))
		{
			$this->setItemValueToModel($name, $currentValue);
		}
	}

	protected function isItemPropertyShouldSetToItem(mixed $currentValue, mixed $originalValue, string $name): bool
	{
		return $this->isValueChanged($currentValue, $originalValue);
	}

	protected function isValueChanged(mixed $current, mixed $original): bool
	{
		$valueToCheckType = $original === null ? $current : $original;
		if ($valueToCheckType instanceof DateTime)
		{
			return $original != $current;
		}

		return $original !== $current;
	}

	protected function setItemValueToModel(string $itemPropertyName, mixed $currentValue): void
	{
		$modelField = $this->getModelFieldByItemProperty($itemPropertyName);
		if (empty($modelField))
		{
			return;
		}

		$modelValue = $this->convertItemValueToModelValue($currentValue, $itemPropertyName);
		$this->model->set($modelField, $modelValue);
	}

	protected function convertItemValueToModelValue(mixed $value, string $itemPropertyName): mixed
	{
		if ($value instanceof IntModelValue)
		{
			return $value->toInt();
		}

		return $value;
	}

	protected function getModelFieldByItemProperty(string $itemProperty): string
	{
		return StringHelper::strtoupper(StringHelper::camel2snake($itemProperty));
	}
}
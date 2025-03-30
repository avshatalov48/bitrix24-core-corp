<?php

namespace Bitrix\Sign\Model\ItemBinder;

use Bitrix\Sign\Item\Fs\FileCollection;

class BlankBinder extends BaseItemToModelBinder
{
	protected function isItemPropertyShouldSetToItem(mixed $currentValue, mixed $originalValue, string $name): bool
	{
		if ($currentValue === null)
		{
			return false;
		}

		if ($currentValue instanceof FileCollection)
		{
			return $this->isFileIdsChanged($currentValue, $originalValue);
		}

		return parent::isItemPropertyShouldSetToItem($currentValue, $originalValue,	$name);
	}

	private function isFileIdsChanged(FileCollection $current, ?FileCollection $original): bool
	{
		if ($original === null)
		{
			return true;
		}

		$currentIds = $current->getIds();
		$originalIds = $original->getIds();
		sort($currentIds);
		sort($originalIds);

		return $currentIds !== $originalIds;
	}

	protected function convertItemValueToModelValue(mixed $value, string $itemPropertyName): mixed
	{
		if ($value instanceof FileCollection)
		{
			return $value->getIds();
		}

		return parent::convertItemValueToModelValue($value, $itemPropertyName);
	}

	protected function getModelFieldByItemProperty(string $itemProperty): string
	{
		return match ($itemProperty)
		{
			'fileCollection' => 'FILE_ID',
			default => parent::getModelFieldByItemProperty($itemProperty),
		};
	}
}
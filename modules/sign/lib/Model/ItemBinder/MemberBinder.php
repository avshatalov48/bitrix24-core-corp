<?php

namespace Bitrix\Sign\Model\ItemBinder;

use Bitrix\Main\Text\StringHelper;
use Bitrix\Sign\Item\Member\Reminder;

class MemberBinder extends BaseItemToModelBinder
{
	protected function setValueToModelIfNeed(mixed $currentValue, mixed $originalValue, string $name): void
	{
		if ($currentValue instanceof Reminder)
		{
			$this->setReminderPropertiesToModel($currentValue, $originalValue, $name);

			return;
		}

		parent::setValueToModelIfNeed($currentValue, $originalValue, $name);
	}

	protected function isItemPropertyShouldSetToItem(mixed $currentValue, mixed $originalValue, string $name): bool
	{
		if ($currentValue === null)
		{
			return false;
		}

		return parent::isItemPropertyShouldSetToItem($currentValue, $originalValue,	$name);
	}

	private function setReminderPropertiesToModel(mixed $currentReminder, mixed $originalReminder, string $containerName): void
	{
		$reflection = new \ReflectionClass($currentReminder);
		$props = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
		foreach ($props as $prop)
		{
			$propertyName = $prop->getName();
			$currentValue = $currentReminder->{$propertyName};
			$originalValue = $originalReminder->{$propertyName};

			$complexPropertyName = $containerName . "_" . StringHelper::camel2snake($propertyName);
			$complexPropertyName = lcfirst(StringHelper::snake2camel($complexPropertyName));
			$this->setValueToModelIfNeed($currentValue, $originalValue, $complexPropertyName);
		}
	}

	protected function getModelFieldByItemProperty(string $itemProperty): string
	{
		return match ($itemProperty)
		{
			'party' => 'PART',
			'status' => 'SIGNED',
			'channelType' => 'COMMUNICATION_TYPE',
			'channelValue' => 'COMMUNICATION_VALUE',
			'dateSigned' => 'DATE_SIGN',
			'hcmLinkJobId' => 'HCMLINK_JOB_ID',
			'uid' => 'HASH',
			default => parent::getModelFieldByItemProperty($itemProperty),
		};
	}
}
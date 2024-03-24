<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Tasks\Util\Entity\DateTimeField;
use Bitrix\Tasks\Util\Type\DateTime;

trait WakeUpTrait
{
	public static function wakeUpObject($data): static
	{
		if (!is_array($data))
		{
			return parent::wakeUp($data);
		}

		$fields = static::$dataClass::getEntity()->getFields();

		$wakeUpData = [];
		$customData = [];
		foreach ($data as $field => $value)
		{
			if (array_key_exists($field, $fields))
			{

				if (
					$fields[$field] instanceof DateTimeField
					&& is_numeric($value)
				)
				{
					$wakeUpData[$field] = DateTime::createFromTimestampGmt($value);
				}
				else
				{
					$wakeUpData[$field] = $value;
				}
			}
			else
			{
				$customData[$field] = $value;
			}
		}

		$object = parent::wakeUp($wakeUpData);
		foreach ($customData as $field => $value)
		{
			$object->customData->set($field, $value);
		}

		return $object;
	}
}
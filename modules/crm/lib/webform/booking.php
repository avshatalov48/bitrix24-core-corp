<?php

namespace Bitrix\Crm\WebForm;

class Booking
{
	public const RESOURCE_FIELD_TYPE = 'booking';

	public static function isResourceForm(array $resultFields): bool
	{
		foreach ($resultFields as $resultField)
		{
			if ($resultField['type'] === self::RESOURCE_FIELD_TYPE)
			{
				return true;
			}
		}

		return false;
	}

	public static function getResourceFieldValueFromResultFields(array $resultFields): ?array
	{
		foreach ($resultFields as $resultField)
		{
			if ($resultField['type'] !== self::RESOURCE_FIELD_TYPE)
			{
				continue;
			}

			return self::getResourceFieldValue($resultField);
		}

		return null;
	}

	public static function getResourceFieldValue(array $field): ?array
	{
		if (
			is_array($field['values'][0])
			&& !empty($field['values'][0])
		)
		{
			return $field['values'][0];
		}

		return null;
	}
}

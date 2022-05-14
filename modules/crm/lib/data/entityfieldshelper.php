<?php

namespace Bitrix\Crm\Data;

abstract class EntityFieldsHelper
{
	/**
	 * @param Array<string, mixed> $fields - associative array where keys are field names, values - field values
	 * @param Array<string, string> $fieldsMap - [currentFieldName => newFieldName]
	 *
	 * @return Array<string, mixed>
	 */
	final public static function replaceFieldNamesByMap(array $fields, array $fieldsMap): array
	{
		foreach ($fieldsMap as $currentFieldName => $newFieldName)
		{
			if (array_key_exists($currentFieldName, $fields))
			{
				$fields[$newFieldName] = $fields[$currentFieldName];
				unset($fields[$currentFieldName]);
			}
		}

		return $fields;
	}
}

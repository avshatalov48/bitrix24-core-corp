<?php
namespace Bitrix\Crm\Filter\Activity;


final class ExtractUsersFromFilter
{
	/**
	 * Extracts user IDs from the filter array based on the given field name.
	 *
	 * @param array  $filter	The filter array to extract user IDs from.
	 * @param string $fieldName	The field name to use for extracting user IDs.
	 *
	 * @return array Returns an array containing user IDs and a boolean value indicating whether the filter
	 * 				contains user IDs to exclude.
	 *				If no user IDs are found, returns an empty array and false.
	 *				If negative user IDs are found, returns an array containing the negative user IDs and true.
	 */
	public function extract(array $filter, string $fieldName): array
	{
		$negativeFieldName = '!' . $fieldName;
		if (!isset($filter[$fieldName]) && !isset($filter[$negativeFieldName]))
		{
			return [[], false];
		}

		if (isset($filter[$negativeFieldName]))
		{
			return $this->excludedUsers($filter[$negativeFieldName]);
		}

		return $this->includeUsers($filter[$fieldName]);
	}

	/**
	 * @param array|int $field
	 * @return array
	 */
	private function includeUsers($field): array
	{
		$counterUserIds = [];
		if (is_array($field))
		{
			$counterUserIds = array_filter($field, 'is_numeric');
		}
		elseif($field > 0)
		{
			$counterUserIds = [$field];
		}
		return [
			$counterUserIds,
			false
		];
	}

	/**
	 * @param array|int $field
	 * @return array
	 */
	private function excludedUsers($field): array
	{
		$counterUserIds = is_array($field) ? $field : [$field];

		return [
			$counterUserIds,
			true
		];
	}
}
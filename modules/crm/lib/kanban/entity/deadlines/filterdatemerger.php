<?php

namespace Bitrix\Crm\Kanban\Entity\Deadlines;


use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\ArgumentException;

/**
 * Old CRM entities using getList do not support non-unique keys in SQL conditions,
 * then when the dates in the filters and the dates in the "Deadlines" columns intersect,
 * the filters must be combined for correct operation.
 */
class FilterDateMerger
{
	/**
	 * Return merged new filter with combined dates or
	 * return a condition that is known to be unsatisfied, since the dates do not intersect
	 * @param array $firstFilter
	 * @param array $secondFilter
	 * @param string $fieldName
	 * @return array
	 */
	public function merge(array $firstFilter, array $secondFilter, string $fieldName): array
	{
		$fromField = ">=$fieldName";
		$toField = "<=$fieldName";
		$resultFilter = [];

		if (!$this->isMergeNecessary($fromField, $toField, $firstFilter, $secondFilter)) {
			return array_merge($firstFilter, $secondFilter);
		}

		[
			'firstFrom' => $firstFrom, 'firstTo' => $firstTo,
			'secondFrom' => $secondFrom, 'secondTo' => $secondTo
		] = $this->prepareDates($firstFilter, $secondFilter, $fieldName);


		// If the dates do not overlap then return a condition that is known to be unsatisfied
		if (
			$firstFrom > $secondTo ||
			$firstTo < $secondFrom
		)
		{
			return array_merge($firstFilter, $secondFilter, [
				'<ID' => 0
			]);
		}

		if ($firstFrom > $secondFrom)
		{
			$resultFilter[$fromField] = $firstFilter[$fromField];
		} else
		{
			$resultFilter[$fromField] = $secondFilter[$fromField];
		}

		if ($secondTo > $firstTo)
		{
			$resultFilter[$toField] = $firstFilter[$toField];
		}
		else
		{
			$resultFilter[$toField] = $secondFilter[$toField];
		}
		return array_merge($firstFilter, $secondFilter, $resultFilter);
	}

	private function prepareDates(array $firstFilter, array $secondFilter, string $field): array
	{
		$result = [];
		$result['firstFrom'] = $this->getOrConvertFieldToDate($firstFilter, ">=$field");
		$result['firstTo'] = $this->getOrConvertFieldToDate($firstFilter, "<=$field");
		$result['secondFrom'] = $this->getOrConvertFieldToDate($secondFilter, ">=$field");
		$result['secondTo'] = $this->getOrConvertFieldToDate($secondFilter, "<=$field");
		return $result;
	}

	/**
	 * @param array $arr
	 * @param string|Date $field
	 * @return int timestamp
	 * @throws ArgumentException
	 * @throws ObjectException
	 */
	private function getOrConvertFieldToDate(array $arr, $field): int
	{
		if ($arr[$field] instanceof Date)
		{
			return $arr[$field]->getTimestamp();
		}
		else if (is_string($arr[$field]))
		{
			return (new Date($arr[$field]))->getTimestamp();
		}
		throw new ArgumentException('Invalid date format ' . $arr[$field]);
	}

	private function isMergeNecessary(string $fromField, string $toField, array $firstFilter, array $secondFilter): bool
	{
		return array_key_exists($fromField, $firstFilter) && array_key_exists($toField, $firstFilter)
			&& array_key_exists($fromField, $secondFilter) && array_key_exists($toField, $secondFilter);
	}

}
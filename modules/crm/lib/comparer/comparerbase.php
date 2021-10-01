<?php
namespace Bitrix\Crm\Comparer;

class ComparerBase
{
	public static function areFieldsEquals(array $left, array $right, $name)
	{
		if(!isset($left[$name]) && !isset($right[$name]))
		{
			return true;
		}
		return isset($left[$name]) && isset($right[$name]) && $left[$name] == $right[$name];
	}

	public function areEquals(array $a, array $b)
	{
		return false;
	}

	/**
	 * Compare two associative arrays and return object that represents a difference between them
	 *
	 * @param array $previousValues
	 * @param array $currentValues
	 *
	 * @return Difference
	 */
	public static function compare(array $previousValues, array $currentValues): Difference
	{
		return new Difference($previousValues, $currentValues);
	}

	/**
	 * Compare fields of a CRM entity and return object that represents a difference between them
	 * Since this method is intended to be specifically used on fields of CRM entities,
	 * some special and sometimes strange comparisons are performed.
	 *
	 * @param array $previousValues
	 * @param array $currentValues
	 *
	 * @return Difference
	 */
	public static function compareEntityFields(array $previousValues, array $currentValues): Difference
	{
		$difference = new Difference($previousValues, $currentValues);

		$difference->configureTreatingAbsentCurrentValueAsNotChanged();

		return $difference;
	}
}

<?php
namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction;

use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction;

/**
 * Class Limit
 *
 * @package Bitrix\Tasks\Util\Restriction\Bitrix24Restriction
 */
class Limit extends Bitrix24Restriction
{
	/**
	 * Checks if limit exceeded
	 *
	 * @param int $limit
	 * @return bool
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function isLimitExceeded(int $limit = 0): bool
	{
		$limit = ($limit > 0 ? $limit : static::getLimit());
		$currentValue = static::getCurrentValue();

		return (static::isLimitExist($limit) ? ($currentValue > $limit) : false);
	}

	/**
	 * Checks if limit exist
	 *
	 * @param int $limit
	 * @return bool
	 */
	public static function isLimitExist(int $limit = 0): bool
	{
		return ($limit > 0 ? true : static::getLimit() > 0);
	}

	/**
	 * Returns current value to compare with limit
	 *
	 * @return int
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected static function getCurrentValue(): int
	{
		return static::getTasksCount();
	}

	/**
	 * Returns limit
	 *
	 * @return int
	 */
	protected static function getLimit(): int
	{
		return max((int)static::getVariable(), 0);
	}
}
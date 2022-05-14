<?php
namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction;

use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

/**
 * Class Limit
 *
 * @package Bitrix\Tasks\Util\Restriction\Bitrix24Restriction
 */
class Limit extends Bitrix24Restriction
{
	public const DEFAULT_LIMIT = 100;
	public const OPTION_LIMIT_KEY = '_tasks_restrict_limit_b';

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

		$optionLimit = static::getOptionLimit();
		if (!is_null($optionLimit))
		{
			$limit = $optionLimit;
			return ($limit === 0) || static::getCurrentValue() > $limit;
		}

		return (static::isLimitExist($limit) && static::getCurrentValue() > $limit);
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

	/**
	 * @return int|null
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected static function getOptionLimit(): ?int
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return null;
		}

		$key = \CBitrix24::getLicenseType().static::OPTION_LIMIT_KEY;

		$value = Option::getRealValue('tasks', $key, '');
		if (!is_null($value))
		{
			$value = (int) $value;
		}

		return $value;
	}
}
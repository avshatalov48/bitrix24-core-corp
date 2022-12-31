<?php
namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

/**
 * Class TaskLimit
 *
 * @package Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit
 */
class ScrumLimit extends Limit
{
	protected static $variableName = FeatureDictionary::VARIABLE_SCRUM_LIMIT;

	private static $maxCount = 21;

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
		if (!Loader::includeModule('bitrix24'))
		{
			return false;
		}

		$limit = ($limit > 0 ? $limit : static::getLimit());

		if ($limit === 0)
		{
			return true;
		}

		return (static::getCurrentValue() >= $limit);
	}

	public static function getSidePanelId(int $limit = 0): string
	{
		$sidePanelId = 'limit_tasks_scrum';

		if (Loader::includeModule('bitrix24'))
		{
			$limit = ($limit > 0 ? $limit : static::getLimit());

			if ($limit === 0)
			{
				$sidePanelId = 'limit_tasks_scrum_restriction';
			}
		}

		return $sidePanelId;
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
		$count = self::$maxCount;

		if (Loader::includeModule('socialnetwork'))
		{
			$query = WorkgroupTable::query();

			$query->setSelect(['ID']);
			$query->setOrder(['ID' => 'DESC']);
			$query->countTotal(true);

			$query->whereNotNull('SCRUM_MASTER_ID');

			$result = $query->exec();

			$count = $result->getCount();
		}

		return $count;
	}
}
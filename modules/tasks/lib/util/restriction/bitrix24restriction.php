<?php
namespace Bitrix\Tasks\Util\Restriction;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Internals\TaskTable;
use CTasks;

/**
 * Class Bitrix24Restriction
 *
 * @package Bitrix\Tasks\Util\Restriction
 */
class Bitrix24Restriction
{
	protected static $variableName = '';

	/**
	 * @return mixed
	 */
	protected static function getVariable()
	{
		return Bitrix24::getVariable(static::$variableName);
	}

	/**
	 * Returns count of active tasks (not deleted, not in recycle bin)
	 *
	 * @return int
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected static function getTasksCount(): int
	{
		$cache = Cache::createInstance();
		$tasksCount = 0;

		if ($cache->initCache(86400, CTasks::CACHE_TASKS_COUNT, CTasks::CACHE_TASKS_COUNT_DIR_NAME))
		{
			$data = $cache->getVars(); // read variables from cache
			$tasksCount = $data['tasks_count'];
		}
		else if ($cache->startDataCache())
		{
			$tasksCount = (int)TaskTable::getCount(['ZOMBIE' => 'N']);
			$cache->endDataCache(['tasks_count' => $tasksCount]); // write to cache
		}

		return $tasksCount;
	}
}
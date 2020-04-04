<?php

namespace Bitrix\Tasks\Util\Restriction;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Internals\TaskTable;

Loc::loadMessages(__FILE__);

/**
 * Class Bitrix24FilterLimitRestriction
 */
class Bitrix24FilterLimitRestriction
{
	/**
	 * Checks if limit exceeded
	 *
	 * @return bool
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function isLimitExceeded()
	{
		$tasksCount = self::getTasksCount();
		$limit = max(Bitrix24::getVariable('tasks_entity_search_limit'), 0);

		return ($limit <= 0? false : $tasksCount >= $limit);
	}

	/**
	 * @return mixed
	 */
	private static function getLimit()
	{
		return max(Bitrix24::getVariable('tasks_entity_search_limit'), 0);
	}

	/**
	 * @return int
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function getTasksCount()
	{
		$cache = Cache::createInstance();
		$tasksCount = 0;

		if ($cache->initCache(86400, \CTasks::FILTER_LIMIT_CACHE_KEY, \CTasks::CACHE_TASKS_COUNT_DIR_NAME))
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

	/**
	 * @param array|null $params
	 * @return array|null
	 */
	public static function prepareStubInfo(array $params = null)
	{
		if ($params === null)
		{
			$params = [];
		}

		if (!isset($params['REPLACEMENTS']))
		{
			$params['REPLACEMENTS'] = [];
		}
		$params['REPLACEMENTS']['#LIMIT#'] = self::getLimit();

		$params['TITLE'] = ($params['TITLE']?: Loc::getMessage("TASKS_RESTRICTION_FILTER_LIMIT_TITLE"));
		$params['CONTENT'] = ($params['CONTENT']?: Loc::getMessage("TASKS_RESTRICTION_FILTER_LIMIT_TEXT"));

		return Bitrix24::prepareStubInfo($params);
	}
}
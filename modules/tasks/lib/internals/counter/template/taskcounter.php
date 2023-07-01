<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Template;

use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Registry\UserRegistry;

class TaskCounter
{
	private $userId;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param int $taskId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getRowCounter(int $taskId): array
	{
		$res = [
			'COLOR' => CounterStyle::STYLE_GRAY,
			'VALUE' => 0,
		];

		if (!$taskId)
		{
			return $res;
		}

		$counters = Counter::getInstance($this->userId)->getTaskCounters($taskId);

		if (empty($counters))
		{
			return $res;
		}

		if (isset($counters[CounterDictionary::COUNTER_MY_NEW_COMMENTS]) && $counters[CounterDictionary::COUNTER_MY_NEW_COMMENTS])
		{
			$res['COLOR'] = CounterStyle::STYLE_GREEN;
			$res['VALUE'] = $counters[CounterDictionary::COUNTER_MY_NEW_COMMENTS];
		}
		if (isset($counters[CounterDictionary::COUNTER_MY_EXPIRED]) && $counters[CounterDictionary::COUNTER_MY_EXPIRED])
		{
			$res['COLOR'] = CounterStyle::STYLE_RED;
			$res['VALUE']++;
		}

		if (!$res['VALUE'])
		{
			if (isset($counters[CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS]))
			{
				$res['VALUE'] = $counters[CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS];
			}
			if (isset($counters[CounterDictionary::COUNTER_MY_MUTED_EXPIRED]) && $counters[CounterDictionary::COUNTER_MY_MUTED_EXPIRED])
			{
				$res['VALUE']++;
			}
		}

		if (!$res['VALUE'])
		{
			if (isset($counters[CounterDictionary::COUNTER_GROUP_COMMENTS]))
			{
				$res['VALUE'] = $counters[CounterDictionary::COUNTER_GROUP_COMMENTS];
			}
			if (
				isset($counters[CounterDictionary::COUNTER_GROUP_EXPIRED])
				&& $counters[CounterDictionary::COUNTER_GROUP_EXPIRED]
			)
			{
				$res['VALUE']++;
				if ($this->isGroupAdmin($taskId))
				{
					$res['COLOR'] = CounterStyle::STYLE_RED;
				}
			}
		}

		return $res;
	}

	public function getMobileRowCounter(int $taskId): array
	{
		$result = [
			'COUNTERS' => [],
			'COLOR' => CounterStyle::STYLE_GRAY,
			'VALUE' => 0,
		];

		if (!$taskId)
		{
			return $result;
		}

		$counters = Counter::getInstance($this->userId)->getTaskCounters($taskId);
		if (!isset($counters))
		{
			$counters = [
				'expired' => 0,
				'new_comments' => 0,
				'project_expired' => 0,
				'project_new_comments' => 0,
			];
		}
		else
		{
			$counters = [
				'expired' => $counters[CounterDictionary::COUNTER_MY_EXPIRED],
				'new_comments' => $counters[CounterDictionary::COUNTER_MY_NEW_COMMENTS],
				'project_expired' => $counters[CounterDictionary::COUNTER_GROUP_EXPIRED],
				'project_new_comments' => $counters[CounterDictionary::COUNTER_GROUP_COMMENTS],
			];
		}

		$result['COUNTERS'] = $counters;
		$result['VALUE'] = array_sum($counters);

		if ($counters['new_comments'] > 0)
		{
			$result['COLOR'] = CounterStyle::STYLE_GREEN;
		}
		if ($counters['expired'] > 0)
		{
			$result['COLOR'] = CounterStyle::STYLE_RED;
		}

		return $result;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 */
	private function isGroupAdmin(int $taskId): bool
	{
		$task = TaskRegistry::getInstance()->get($taskId);

		if (!$task['GROUP_ID'])
		{
			return false;
		}
		$groupId = (int)$task['GROUP_ID'];

		return UserRegistry::getInstance($this->userId)->isGroupAdmin($groupId);
	}
}
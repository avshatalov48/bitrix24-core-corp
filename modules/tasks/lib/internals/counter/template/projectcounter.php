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
use Bitrix\Tasks\Internals\Registry\UserRegistry;

class ProjectCounter
{
	private $userId;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param int $groupId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getRowCounter(int $groupId): array
	{
		$result = [
			'COUNTERS' => [],
			'COLOR' => CounterStyle::STYLE_GRAY,
			'VALUE' => 0,
		];

		if (!$groupId)
		{
			return $result;
		}

		$counter = Counter::getInstance($this->userId);
		$counters = [
			'expired' => $counter->get(CounterDictionary::COUNTER_EXPIRED, $groupId),
			'new_comments' => $counter->get(CounterDictionary::COUNTER_NEW_COMMENTS, $groupId),
			'project_expired' => $counter->get(CounterDictionary::COUNTER_GROUP_EXPIRED, $groupId),
			'project_new_comments' => $counter->get(CounterDictionary::COUNTER_GROUP_COMMENTS, $groupId),
		];

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
}

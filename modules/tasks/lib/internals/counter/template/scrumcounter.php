<?php

namespace Bitrix\Tasks\Internals\Counter\Template;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;

class ScrumCounter
{
	private int $userId;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param int $groupId
	 * @return array
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
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
			'new_comments' => $counter->get(CounterDictionary::COUNTER_NEW_COMMENTS, $groupId),
			'project_new_comments' => $counter->get(CounterDictionary::COUNTER_GROUP_COMMENTS, $groupId),
		];

		$result['COUNTERS'] = $counters;
		$result['VALUE'] = array_sum($counters);

		if ($counters['new_comments'] > 0)
		{
			$result['COLOR'] = CounterStyle::STYLE_GREEN;
		}

		return $result;
	}
}

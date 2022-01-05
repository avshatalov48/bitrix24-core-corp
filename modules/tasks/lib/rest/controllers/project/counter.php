<?php

namespace Bitrix\Tasks\Rest\Controllers\Project;

use Bitrix\Tasks\Internals;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Rest\Controllers\Base;

class Counter extends Base
{
	public function getTotalAction(int $userId, array $counterTypes = []): array
	{
		if (empty($counterTypes))
		{
			$counterTypes = [
				CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED,
				CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS,
				CounterDictionary::COUNTER_SONET_FOREIGN_EXPIRED,
				CounterDictionary::COUNTER_SONET_FOREIGN_COMMENTS,
			];
		}

		$counterProvider = Internals\Counter::getInstance($userId);

		$counters = [];
		foreach ($counterTypes as $type)
		{
			$counters[$type] = $counterProvider->get($type);
		}

		return $this->convertKeysToCamelCase($counters);
	}
}
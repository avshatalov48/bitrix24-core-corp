<?php

namespace Bitrix\Tasks\Components\Kanban\Services;

class Time
{
	/**
	 * Fill data-array with time starting delta.
	 * @param array $items Task items.
	 * @return array
	 */
	public function getTimeStarted(array $items): array
	{
		if (empty($items))
		{
			return $items;
		}

		$res = \Bitrix\Tasks\Internals\Task\TimerTable::getList([
			'filter' => array(
				'TASK_ID' => array_keys($items),
				'>TIMER_STARTED_AT' => 0
			)
		]);
		while ($row = $res->fetch())
		{
			$delta = time() - $row['TIMER_STARTED_AT'];
			$items[$row['TASK_ID']]['data']['time_logs'] += $delta;
			//$items[$row['TASK_ID']]['data']['time_logs_start'] += $delta;
		}

		return $items;
	}
}
<?php

namespace Bitrix\Crm\Counter\Lighter;

use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Type\DateTime;

final class LighterQueriesTransition extends LighterQueries
{
	/**
	 * @inheritdoc
	 */
	public function queryActivityIdsToLightCounters(): array
	{
		$fakeLightTime = new DateTime();
		$fakeLightTime->add('PT15M');

		$subQ = ActCounterLightTimeTable::query()
			->setSelect(['ACTIVITY_ID'])
			->where('IS_LIGHT_COUNTER_NOTIFIED', '=', 'N');

		$query = ActivityTable::query()
			->setSelect(['ID'])
			->where('COMPLETED', '=', 'N')
			->where('DEADLINE', '<', $fakeLightTime)
			->whereIn('ID', $subQ)
			->setLimit(100);

		return array_column($query->fetchAll(), 'ID');
	}

	/**
	 * @inheritdoc
	 */
	public function queryActivitiesByIds(array $ids): array
	{
		if (empty($ids))
		{
			return [];
		}

		$res = \CCrmActivity::GetList([], ['ID' => $ids, 'CHECK_PERMISSIONS' => 'N']);

		$items = [];
		while ($activity = $res->Fetch())
		{
			$deadline = $activity['DEADLINE'];
			$lightTime = null;
			if ($deadline)
			{
				$lightTime = new DateTime($deadline);
				$lightTime->add('-PT15M');
			}

			$activity['LIGHT_COUNTER_AT'] = $lightTime;
			$items[] = $activity;
		}
		return  $items;
	}
}
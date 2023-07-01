<?php

namespace Bitrix\Crm\Counter\Lighter;

use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeRepo;
use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeTable;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Type\DateTime;

final class LighterQueriesCommon extends LighterQueries
{
	/**
	 * @inheritdoc
	 */
	public function queryActivityIdsToLightCounters(): array
	{
		$lightTimeQuery = ActCounterLightTimeTable::query()
			->addSelect('ACTIVITY_ID')
			->where('LIGHT_COUNTER_AT', '<=', new DateTime())
			->where('IS_LIGHT_COUNTER_NOTIFIED', '=', 'N')
			->addOrder('LIGHT_COUNTER_AT', 'DESC')
			->setLimit(100);

		return array_column($lightTimeQuery->fetchAll(), 'ACTIVITY_ID');
	}

	/**
	 * @inheritdoc
	 */
	public  function queryActivitiesByIds(array $ids): array
	{
		if (empty($ids))
		{
			return [];
		}

		$res = \CCrmActivity::GetList([], ['ID' => $ids, 'CHECK_PERMISSIONS' => 'N']);

		$items = [];
		while ($activity = $res->Fetch())
		{
			$items[] = $activity;
		}

		/** @var ActCounterLightTimeRepo $lightCounterRepo */
		$lightCounterRepo = ServiceLocator::getInstance()->get('crm.activity.actcounterlighttimerepo');
		$arrLightTimeAt = $lightCounterRepo->queryLightTimeByActivityIds($ids);

		foreach ($items as &$item)
		{
			$actId = $item['ID'];
			$item['LIGHT_COUNTER_AT'] = $arrLightTimeAt[$actId] ?? null;
		}

		return $items;
	}
}
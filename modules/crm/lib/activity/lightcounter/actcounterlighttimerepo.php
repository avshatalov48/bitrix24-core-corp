<?php

namespace Bitrix\Crm\Activity\LightCounter;

use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use CCrmDateTimeHelper;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;


class ActCounterLightTimeRepo
{
	private bool $isTransitionalMode;

	public function __construct()
	{
		$this->isTransitionalMode = Option::get('crm', 'enable_act_counter_light', 'Y') !== 'Y';
	}

	public function queryLightTimeByActivityId(int $activityId): ?DateTime
	{
		$row = ActCounterLightTimeTable::query()
			->addSelect('LIGHT_COUNTER_AT')
			->where('ACTIVITY_ID', '=', $activityId)
			->fetch();

		if ($row === false || empty($row['LIGHT_COUNTER_AT']))
		{
			return null;
		}
		return $row['LIGHT_COUNTER_AT'];
	}


	public function minLightTimeByItemIdentifier(ItemIdentifier $identifier, ?int $responsibleId = null): DateTime
	{
		$query = ActCounterLightTimeTable::query()
			->addSelect('LIGHT_COUNTER_AT')
			->registerRuntimeField(
				'',
				new ReferenceField('B',
					ActivityBindingTable::getEntity(),
					['=ref.ACTIVITY_ID' => 'this.ACTIVITY_ID'],
				)
			)
			->where('B.OWNER_ID', '=', $identifier->getEntityId())
			->where('B.OWNER_TYPE_ID', '=', $identifier->getEntityTypeId())
			->addOrder('LIGHT_COUNTER_AT', 'ASC')
			->setLimit(1);

		if ($responsibleId !== null)
		{
			$query->registerRuntimeField(
				'',
				new ReferenceField('A',
					ActivityTable::getEntity(),
					['=ref.ID' => 'this.ACTIVITY_ID'],
				)
			);
			$query->where('A.RESPONSIBLE_ID', $responsibleId);
		}

		$row = $query->fetch();

		return $row['LIGHT_COUNTER_AT'] ?? CCrmDateTimeHelper::getMaxDatabaseDateObject();
	}

	/**
	 * @param int $entityTypeId
	 * @param int[] $entityIds
	 * @return array
	 */
	public function activitiesWithLightTimeByEntityIds(int $entityTypeId, array $entityIds): array
	{
		if (empty($entityIds))
		{
			return [];
		}

		$queryBuilder = ActivityTable::query()
			->addSelect('ID')
			->addSelect('COMPLETED')
			->addSelect('B.OWNER_ID', 'BIND_OWNER_ID')
			->addSelect('B.OWNER_TYPE_ID', 'BIND_OWNER_TYPE_ID')
			->addSelect('RESPONSIBLE_ID')
			->addSelect('DEADLINE')
			->registerRuntimeField(
				'',
				new ReferenceField('B',
					ActivityBindingTable::getEntity(),
					[
						'=ref.ACTIVITY_ID' => 'this.ID',
					]
				)
			)
			->registerRuntimeField(
				'',
				new ReferenceField('LT',
					ActCounterLightTimeTable::getEntity(),
					[
						'=ref.ACTIVITY_ID' => 'this.ID',
					]
				)
			)
			->whereIn('B.OWNER_ID', $entityIds)
			->where('B.OWNER_TYPE_ID', '=', $entityTypeId);

		// If LIGHT_COUNTER_AT not filled yet by update agent then will use activate counter before 15 minutes to deadline
		if ($this->isTransitionalMode)
		{
			$almostDeadline = Application::getConnection()->getSqlHelper()->addSecondsToDateTime(-15*60, 'DEADLINE');
			$queryBuilder
				->registerRuntimeField(new ExpressionField(
						'LIGHT_COUNTER_AT',
						"COALESCE(crm_activity_lt.LIGHT_COUNTER_AT, $almostDeadline)"
					)
				)
				->addSelect('LIGHT_COUNTER_AT');
		}
		else
		{
			$queryBuilder
				->addSelect('LT.LIGHT_COUNTER_AT', 'LIGHT_COUNTER_AT');
		}

		return $queryBuilder->fetchAll();
	}

	/**
	 * @param int[] $activityIds
	 * @return array<integer, DateTime|null> - key is an ActivityId, value is a light counter Datetime
	 */
	public function queryLightTimeByActivityIds(array $activityIds): array
	{
		if (empty($activityIds))
		{
			return [];
		}

		if ($this->isTransitionalMode)
		{
			$rows = $this->queryLightTimeByActivityIdsTransitional($activityIds);
		}
		else
		{
			$rows = $this->queryLightTimeByActivityIdsCommon($activityIds);
		}

		if (count($rows) === 0)
		{
			return [];
		}
		$result = [];
		foreach ($rows as $row)
		{
			$result[$row['ACTIVITY_ID']] = $row['LIGHT_COUNTER_AT'];
		}
		return $result;
	}

	private function queryLightTimeByActivityIdsTransitional(array $activityIds): array
	{
		$almostDeadline = Application::getConnection()->getSqlHelper()->addSecondsToDateTime(-15*60, 'DEADLINE');

		$queryBuilder = ActivityTable::query()
			->addSelect('ID', 'ACTIVITY_ID')
			->addSelect('LIGHT_COUNTER_AT')
			->registerRuntimeField(
				'',
				new ReferenceField('LT',
					ActCounterLightTimeTable::getEntity(),
					[
						'=ref.ACTIVITY_ID' => 'this.ID',
					]
				)
			)
			->registerRuntimeField(new ExpressionField(
					'LIGHT_COUNTER_AT',
					"COALESCE(crm_activity_lt.LIGHT_COUNTER_AT, $almostDeadline)"
				)
			)
			->whereIn('ID', $activityIds);

		return $queryBuilder->fetchAll();
	}

	private function queryLightTimeByActivityIdsCommon(array $activityIds): array
	{
		return ActCounterLightTimeTable::query()
			->addSelect('LIGHT_COUNTER_AT')
			->addSelect('ACTIVITY_ID')
			->whereIn('ACTIVITY_ID', $activityIds)
			->fetchAll();
	}

}
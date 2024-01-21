<?php

namespace Bitrix\Crm\Counter\ProblemDetector;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeTable;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;

class ProblemDetectorQueries
{
	use Singleton;

	/**
	 * @return int[]
	 */
	public function queryCountableCompletedIds(int $limit = 50): array
	{
		$query = EntityCountableActivityTable::query()
			->setSelect(['ID'])
			->registerRuntimeField(
				'',
				new ReferenceField('A',
					ActivityTable::getEntity(),
					['=ref.ID' => 'this.ACTIVITY_ID'],
					['join_type' => Join::TYPE_LEFT]
				),
			)
			->where('A.COMPLETED', true)
			->setLimit($limit);

		return array_column($query->fetchAll(), 'ID');
	}

	/**
	 * @return int[]
	 */
	public function queryCountableDeletedIds(int $limit = 50): array
	{
		$query = EntityCountableActivityTable::query()
			->setSelect(['ID'])
			->registerRuntimeField(
				'',
				new ReferenceField('A',
					ActivityTable::getEntity(),
					['=ref.ID' => 'this.ACTIVITY_ID'],
					['join_type' => Join::TYPE_LEFT]
				),
			)
			->whereNull('A.ID')
			->setLimit($limit);

		return array_column($query->fetchAll(), 'ID');
	}

	public function queryCountableFields(array $entityCountableIds): array
	{
		return EntityCountableActivityTable::query()
			->setSelect([
				'ID',
				'ENTITY_TYPE_ID',
				'ENTITY_ID',
				'ACTIVITY_ID',
				'ACTIVITY_DEADLINE',
				'ACTIVITY_RESPONSIBLE_ID',
				'ACTIVITY_IS_INCOMING_CHANNEL'
			])
			->whereIn('ID', $entityCountableIds)
			->fetchAll();
	}

	public function queryActivities(array $activityIds): array
	{
		return ActivityTable::query()
			->setSelect([
				'ID',
				'PROVIDER_ID',
				'PROVIDER_TYPE_ID',
				'DEADLINE',
				'LAST_UPDATED'
			])
			->whereIn('ID', $activityIds)
			->fetchAll();
	}

	public function queryActBindings(array $activityIds): array
	{
		return ActivityBindingTable::query()
			->setSelect([
				'ACTIVITY_ID',
				'OWNER_ID',
				'OWNER_TYPE_ID'
			])
			->whereIn('ACTIVITY_ID', $activityIds)
			->fetchAll();
	}

	public function queryUncompletedCompletedIds(int $limit = 50): array
	{
		$query = EntityUncompletedActivityTable::query()
			->setSelect(['ID'])
			->registerRuntimeField(
				'',
				new ReferenceField('A',
					ActivityTable::getEntity(),
					['=ref.ID' => 'this.ACTIVITY_ID'],
					['join_type' => Join::TYPE_LEFT]
				),
			)
			->where('A.COMPLETED', true)
			->setLimit($limit);

		return array_column($query->fetchAll(), 'ID');
	}

	public function queryUncompletedDeletedIds(int $limit = 50): array
	{
		$query = EntityUncompletedActivityTable::query()
			->setSelect(['ID'])
			->registerRuntimeField(
				'',
				new ReferenceField('A',
					ActivityTable::getEntity(),
					['=ref.ID' => 'this.ACTIVITY_ID'],
					['join_type' => Join::TYPE_LEFT]
				),
			)
			->whereNull('A.ID')
			->setLimit($limit);

		return array_column($query->fetchAll(), 'ID');
	}

	public function queryUncompletedFields(array $uncompletedIds): array
	{
		return EntityUncompletedActivityTable::query()
			->setSelect([
				'ID',
				'ENTITY_TYPE_ID',
				'ENTITY_ID',
				'ACTIVITY_ID',
				'RESPONSIBLE_ID',
				'MIN_DEADLINE',
				'IS_INCOMING_CHANNEL',
				'HAS_ANY_INCOMING_CHANEL',
				'MIN_LIGHT_COUNTER_AT'
			])
			->whereIn('ID', $uncompletedIds)
			->fetchAll();
	}

	public function queryLightCounterCompletedIds(int $limit = 50): array
	{
		$query = ActCounterLightTimeTable::query()
			->setSelect(['ACTIVITY_ID'])
			->registerRuntimeField(
				'',
				new ReferenceField('A',
					ActivityTable::getEntity(),
					['=ref.ID' => 'this.ACTIVITY_ID'],
					['join_type' => Join::TYPE_LEFT]
				),
			)
			->where('A.COMPLETED', true)
			->setLimit($limit);

		return array_column($query->fetchAll(), 'ACTIVITY_ID');
	}

	public function getActivityFields(array $activitiesIds): array
	{
		$activityRecords = $this->queryActivities($activitiesIds);
		$bindings = $this->groupBindingsByActivityId(
			$this->queryActBindings($activitiesIds)
		);

		foreach ($activityRecords as &$activityRecord)
		{
			$actId = (string)$activityRecord['ID'];
			$activityRecord['BINDINGS'] = array_slice($bindings[$actId] ?? [], 0, 15);
		}

		return $activityRecords;
	}

	private function groupBindingsByActivityId(array $bindings): array
	{
		$result = [];
		foreach ($bindings as $binding)
		{
			$actId = (string)$binding['ACTIVITY_ID'];
			if (!isset($result[$actId]))
			{
				$result[$actId] = [];
			}

			$result[$actId][] = [(int)$binding['OWNER_TYPE_ID'], (int)$binding['OWNER_ID']];
		}

		return $result;
	}

}
<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Update\Stepper;

class SynchronizeUncompletedActivityDataAgent extends Stepper
{
	protected static $moduleId = 'crm';

	private const ENTITY_UNCOMPLETED_ACTIVITY_TABLE = 'EntityUncompletedActivity';
	private const INCOMING_CHANNEL_TABLE = 'IncomingChannelTable';

	public function execute(array &$result)
	{
		$result['steps'] = (int)($result['steps'] ?? 0);
		if (!$result['currentTable'])
		{
			$result['currentTable'] = self::INCOMING_CHANNEL_TABLE;
		}
		switch ($result['currentTable'])
		{
			case self::INCOMING_CHANNEL_TABLE:
				$result = $this->processIncomingChannelTable($result);
				return self::CONTINUE_EXECUTION;

			case self::ENTITY_UNCOMPLETED_ACTIVITY_TABLE:
				$result = $this->processEntityUncompletedActivityTable($result);
				return self::CONTINUE_EXECUTION;
		}

		$this->onStepperComplete();

		return self::FINISH_EXECUTION;
	}

	private function getLimit(): int
	{
		return (int)Option::get('crm', 'FixUncompletedActivityDeadlineAgent', 200);
	}

	private function onStepperComplete(): void
	{
		\COption::RemoveOption('crm', 'enable_any_incoming_act');
	}

	private function getUncompletedActivityList(int $lastId, int $limit): array
	{
		$uncompletedActivities = EntityUncompletedActivityTable::query()
			->setSelect([
				'ID',
				'ACTIVITY_ID',
				'MIN_DEADLINE',
				'IS_INCOMING_CHANNEL',
				'ENTITY_TYPE_ID',
				'ENTITY_ID',
			])
			->where('ID', '>', $lastId)
			->setLimit($limit)
			->setOrder(['ID' => 'ASC'])
			->fetchAll()
		;
		$activitiesIds = array_column($uncompletedActivities, 'ACTIVITY_ID');
		if (empty($activitiesIds))
		{
			return [];
		}
		$activitiesIds = array_unique($activitiesIds);

		$activitiesDeadlines = [];
		$activitiesDeadlinesIterator = ActivityTable::query()
			->whereIn('ID', $activitiesIds)
			->setSelect(['DEADLINE', 'ID'])
			->exec()
		;
		while ($activity = $activitiesDeadlinesIterator->fetch())
		{
			$activitiesDeadlines[$activity['ID']] = $activity['DEADLINE'];
		}

		foreach ($uncompletedActivities as $i => $uncompletedActivity)
		{
			$uncompletedActivities[$i]['REAL_DEADLINE'] =
				$activitiesDeadlines[$uncompletedActivity['ACTIVITY_ID']] ?? $uncompletedActivity['MIN_DEADLINE']
			;
		}

		return $uncompletedActivities;
	}

	private function processEntityUncompletedActivityTable(array $result): array
	{
		$limit = $this->getLimit();
		$lastId = ($result['lastUncompletedActivityId'] ?? 0);
		$processedCount = 0;

		$items = $this->getUncompletedActivityList($lastId, $limit);

		foreach ($items as $item)
		{
			$lastId = (int)$item['ID'];
			$result['steps']++;
			$processedCount++;

			if (!$item['REAL_DEADLINE'] instanceof DateTime)
			{
				continue;
			}

			if (
				$item['MIN_DEADLINE'] instanceof DateTime
				&& $item['MIN_DEADLINE']->getTimestamp() === $item['REAL_DEADLINE']->getTimestamp()
			)
			{
				continue;
			}

			$minDeadline = clone $item['REAL_DEADLINE'];
			$minDeadline->disableUserTime();

			EntityUncompletedActivityTable::update($item['ID'], ['MIN_DEADLINE' => $minDeadline]);
		}

		$result['lastUncompletedActivityId'] = $lastId;

		if ($processedCount < $limit)
		{
			$result['currentTable'] = '-';
		}

		return $result;
	}

	private function processIncomingChannelTable(array $result): array
	{
		$limit = $this->getLimit();
		$lastId = ($result['lastIncomingActivityId'] ?? 0);
		$processedCount = 0;

		$items = $this->getIncomingActivityList($lastId, $limit);
		foreach ($items as $item)
		{
			$lastId = (int)$item['ID'];
			$result['steps']++;
			$processedCount++;

			$activityId = (int)$item['ACTIVITY_ID'];
			$bindings = \CCrmActivity::GetBindings($activityId);
			foreach ($bindings as $binding)
			{
				$ownerTypeId = (int)$binding['OWNER_TYPE_ID'];
				$ownerId = (int)$binding['OWNER_ID'];
				$this->setHasAnyIncomingChannelForResponsible($ownerTypeId, $ownerId, $item['RESPONSIBLE_ID']);
				$this->setHasAnyIncomingChannelForResponsible($ownerTypeId, $ownerId, 0);
			}
		}

		$result['lastIncomingActivityId'] = $lastId;
		if ($processedCount < $limit)
		{
			$result['currentTable'] = self::ENTITY_UNCOMPLETED_ACTIVITY_TABLE;
		}

		return $result;
	}

	private function getIncomingActivityList(int $lastId, int $limit): array
	{
		return IncomingChannelTable::query()
			->setSelect([
				'ID',
				'ACTIVITY_ID',
				'RESPONSIBLE_ID',
			])
			->where('ID', '>', $lastId)
			->where('COMPLETED', false)
			->setLimit($limit)
			->setOrder(['ID' => 'ASC'])
			->fetchAll()
		;
	}

	private function setHasAnyIncomingChannelForResponsible(int $ownerTypeId, int $ownerId, int $responsibleId): void
	{
		$existedUncompletedActivity = EntityUncompletedActivityTable::query()
			->where('RESPONSIBLE_ID', $responsibleId)
			->where('ENTITY_TYPE_ID', $ownerTypeId)
			->where('ENTITY_ID', $ownerId)
			->setSelect(['ID', 'HAS_ANY_INCOMING_CHANEL'])
			->fetch()
		;
		if ($existedUncompletedActivity && $existedUncompletedActivity['HAS_ANY_INCOMING_CHANEL'] === 'N')
		{
			EntityUncompletedActivityTable::update($existedUncompletedActivity['ID'], ['HAS_ANY_INCOMING_CHANEL' => true]);
		}
	}
}

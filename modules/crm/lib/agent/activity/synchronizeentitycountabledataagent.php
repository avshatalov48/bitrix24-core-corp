<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Activity\UncompletedActivity;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Update\Stepper;

class SynchronizeEntityCountableDataAgent extends Stepper
{
	protected static $moduleId = 'crm';

	private const ENTITY_COUNTABLE_TABLE = 'EntityCountableActivity';
	private const ENTITY_UNCOMPLETED_ACTIVITY_TABLE = 'EntityUncompletedActivity';

	public function execute(array &$result)
	{
		if (Option::get('crm', 'enable_entity_countable_act', 'Y') !== 'Y')
		{
			return self::CONTINUE_EXECUTION; // wait ProcessEntityCountableActivitiesAgent to finish
		}
		if (Option::get('crm', 'enable_any_incoming_act', 'Y') !== 'Y')
		{
			return self::CONTINUE_EXECUTION; // wait SynchronizeUncompletedActivityDataAgent to finish
		}

		$result['steps'] = (int)($result['steps'] ?? 0);
		if (!isset($result['currentTable']))
		{
			$result['currentTable'] = self::ENTITY_COUNTABLE_TABLE;
		}
		switch ($result['currentTable'])
		{
			case self::ENTITY_COUNTABLE_TABLE:
				$result = $this->processEntityCountableTable($result);
				return self::CONTINUE_EXECUTION;

			case self::ENTITY_UNCOMPLETED_ACTIVITY_TABLE:
				$result = $this->processEntityUncompletedActivityTable($result);
				return self::CONTINUE_EXECUTION;
		}

		return self::FINISH_EXECUTION;
	}

	private function getLimit(): int
	{
		return (int)Option::get('crm', 'SynchronizeEntityCountableDataAgent', 100);
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

			if (
				$item['COMPLETED']
				|| $item['DELETED']
			) // if EntityUncompletedActivityTable contain link to wrong activity
			{
				$entityTypeId = (int)$item['ENTITY_TYPE_ID'];
				$entityId = (int)$item['ENTITY_ID'];
				if (\CCrmOwnerType::IsDefined($entityTypeId) && $entityId > 0)
				{
					(new UncompletedActivity(new ItemIdentifier($entityTypeId, $entityId),
						(int)$item['RESPONSIBLE_ID']))
						->synchronize();
				}
				continue;
			}

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

	private function getUncompletedActivityList(int $lastId, int $limit): array
	{
		$uncompletedActivities = EntityUncompletedActivityTable::query()
			->setSelect([
				'ID',
				'ACTIVITY_ID',
				'ENTITY_TYPE_ID',
				'ENTITY_ID',
				'RESPONSIBLE_ID',
				'MIN_DEADLINE',
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

		$activitiesData = [];
		$activitiesDeadlinesIterator = ActivityTable::query()
			->whereIn('ID', $activitiesIds)
			->setSelect(['COMPLETED', 'ID', 'DEADLINE'])
			->exec();
		while ($activity = $activitiesDeadlinesIterator->fetch())
		{
			$activitiesData[$activity['ID']] = [
				'COMPLETED' => ($activity['COMPLETED'] === 'Y'),
				'DEADLINE' => $activity['DEADLINE'],
			];
		}

		foreach ($uncompletedActivities as $i => $uncompletedActivity)
		{
			$uncompletedActivities[$i]['COMPLETED'] = (bool)$activitiesData[$uncompletedActivity['ACTIVITY_ID']]['COMPLETED'];
			$uncompletedActivities[$i]['DELETED'] = !array_key_exists($uncompletedActivity['ACTIVITY_ID'],
				$activitiesData);
			$uncompletedActivities[$i]['REAL_DEADLINE'] =
				$activitiesData[$uncompletedActivity['ACTIVITY_ID']]['DEADLINE'] ?? $uncompletedActivity['MIN_DEADLINE'];
		}

		return $uncompletedActivities;
	}

	private function processEntityCountableTable(array $result): array
	{
		$limit = $this->getLimit();
		$lastId = ($result['lastEntityCountableActId'] ?? 0);
		$processedCount = 0;

		$activitiesIds = $this->getEntityCountableActivitiesIds($lastId, $limit);
		foreach ($activitiesIds as $activityId)
		{
			$lastId = (int)$activityId;
			$result['steps']++;
			$processedCount++;

			$activityBindingsMap = [];
			$bindings = \CCrmActivity::GetBindings($activityId);
			foreach ($bindings as $binding)
			{
				$ownerTypeId = (int)$binding['OWNER_TYPE_ID'];
				$ownerId = (int)$binding['OWNER_ID'];
				$activityBindingsMap[$ownerTypeId . '_' . $ownerId] = true;
			}
			$counterBindings = $this->getEntityCountableActivitiesBindings($activityId);
			foreach ($counterBindings as $binding)
			{
				$entityTypeId = (int)$binding['ENTITY_TYPE_ID'];
				$entityId = (int)$binding['ENTITY_ID'];
				// remove wrong records from EntityCountableActivityTable:
				if (!$activityBindingsMap[$entityTypeId . '_' . $entityId])
				{
					EntityCountableActivityTable::delete($binding['ID']);
					$this->resetCounterByEntity($entityTypeId,  $entityId);
				}
			}
		}

		$result['lastEntityCountableActId'] = $lastId;
		if ($processedCount < $limit)
		{
			$result['currentTable'] = self::ENTITY_UNCOMPLETED_ACTIVITY_TABLE;
		}

		return $result;
	}

	private function getEntityCountableActivitiesIds(int $lastId, int $limit): array
	{
		$data = EntityCountableActivityTable::query()
			->setSelect([
				'ACTIVITY_ID',
			])
			->where('ACTIVITY_ID', '>', $lastId)
			->setLimit($limit)
			->setOrder(['ACTIVITY_ID' => 'ASC'])
			->setGroup(['ACTIVITY_ID'])
			->fetchAll()
		;
		return array_column($data, 'ACTIVITY_ID');
	}

	private function getEntityCountableActivitiesBindings(int $activityId): array
	{
		return EntityCountableActivityTable::query()
			->setSelect([
				'ACTIVITY_ID',
				'ENTITY_TYPE_ID',
				'ENTITY_ID',
				'ID',
			])
			->where('ACTIVITY_ID', $activityId)
			->fetchAll()
		;
	}

	private function resetCounterByEntity($entityTypeId,  $entityId): void
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return;
		}
		if (!$factory->isCountersEnabled())
		{
			return;
		}
		$assignedByFiledName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
		$categoryIdFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_CATEGORY_ID);

		$select = [
			Item::FIELD_NAME_ID,
		];
		if ($factory->isFieldExists(Item::FIELD_NAME_ASSIGNED))
		{
			$select[] = $assignedByFiledName;
		}
		if ($factory->isFieldExists(Item::FIELD_NAME_CATEGORY_ID))
		{
			$select[] = $categoryIdFieldName;
		}

		$entity = $factory->getDataClass()::query()
			->setSelect($select)
			->whereIn('ID', $entityId)
			->fetch()
		;
		if (!$entity)
		{
			return;
		}
		$extras = [];
		if ($factory->isCategoriesEnabled())
		{
			$extras['CATEGORY_ID'] = $entity[$categoryIdFieldName];
		}
		$responsibleId = $entity[$assignedByFiledName];

		$counterCodes = EntityCounterManager::prepareCodes(
			$entityTypeId,
			EntityCounterType::getAll(true),
			$extras
		);

		if(!empty($counterCodes))
		{
			EntityCounterManager::reset($counterCodes, [$responsibleId]);
			EntityCounterManager::resetExcludeUsersCounters($counterCodes, [$responsibleId]);
		}
	}
}

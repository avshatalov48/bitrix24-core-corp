<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Update\Stepper;

class SyncCountableDataAgent extends Stepper
{
	private const STEP_COUNTABLE_ACTIVITIES = 'CountableActivities';
	private const STEP_COUNTABLE_ENTITIES = 'CountableEntities';
	private const STEP_UNCOMPLETED_ACTIVITIES = 'UncompletedActivities';

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
		if ($this->isEntityCountableDataAgentExists())
		{
			return self::CONTINUE_EXECUTION; // wait SynchronizeEntityCountableDataAgent to finish
		}

		$result['steps'] = (int)($result['steps'] ?? 0);

		if (!isset($result['currentStep']))
		{
			$result['currentStep'] = self::STEP_COUNTABLE_ACTIVITIES;
		}
		switch ($result['currentStep'])
		{
			case self::STEP_COUNTABLE_ACTIVITIES:
				$result = $this->processCountableActivities($result);
				return self::CONTINUE_EXECUTION;

			case self::STEP_COUNTABLE_ENTITIES:
				$result = $this->processCountableEntities($result);
				return self::CONTINUE_EXECUTION;

			case self::STEP_UNCOMPLETED_ACTIVITIES:
				$result = $this->processEntityUncompletedActivities($result);
				return self::CONTINUE_EXECUTION;
		}

		return self::FINISH_EXECUTION;
	}

	private function getLimit(): int
	{
		return (int)Option::get('crm', 'SyncCountableDataAgent', 200);
	}

	private function getLinkedItemsLimit(): int
	{
		return (int)Option::get('crm', 'SyncCountableDataAgentActLimit', 1000);
	}

	private function getCountableActivityList(int $lastId, int $limit): array
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
		$activityIds = array_column($data, 'ACTIVITY_ID');
		if (empty($activityIds))
		{
			return [];
		}

		$activityIds = array_unique($activityIds);
		sort($activityIds);

		$activitiesData = [];
		$activitiesDeadlinesIterator = ActivityTable::query()
			->whereIn('ID', $activityIds)
			->setSelect(['COMPLETED', 'ID', 'DEADLINE', 'RESPONSIBLE_ID'])
			->exec();
		while ($activity = $activitiesDeadlinesIterator->fetch())
		{
			$activitiesData[$activity['ID']] = [
				'COMPLETED' => ($activity['COMPLETED'] === 'Y'),
				'DEADLINE' => $activity['DEADLINE'],
				'RESPONSIBLE_ID' => $activity['RESPONSIBLE_ID'],
			];
		}
		$incomingChannelActivityIds = array_column(
			IncomingChannelTable::query()
				->setSelect([
					'ACTIVITY_ID',
				])
				->whereIn('ACTIVITY_ID', $activityIds)
				->fetchAll(),
			'ACTIVITY_ID'
		);

		$result = [];
		foreach ($activityIds as $activityId)
		{
			$result[] = [
				'ID' => $activityId,
				'DELETED' => !array_key_exists($activityId, $activitiesData),
				'COMPLETED' => (bool)$activitiesData[$activityId]['COMPLETED'],
				'DEADLINE' => $activitiesData[$activityId]['DEADLINE'] ?? null,
				'RESPONSIBLE_ID' => $activitiesData[$activityId]['RESPONSIBLE_ID'] ?? null,
				'IS_INCOMING_CHANNEL' => in_array($activityId, $incomingChannelActivityIds, false) ? 'Y' : 'N'
			];
		}

		return $result;
	}

	private function getCountableEntityList(int &$lastEntityTypeId, int &$lastEntityId, int $limit): array
	{
		$dataByEntityTypeId = [];
		$iterator = EntityCountableActivityTable::query()
			->setSelect([
				'ENTITY_ID',
				'ENTITY_TYPE_ID',
			])
			->where('ENTITY_ID', $lastEntityId)
			->where('ENTITY_TYPE_ID', '>', $lastEntityTypeId)
			->setOrder(['ENTITY_ID' => 'ASC', 'ENTITY_TYPE_ID' => 'ASC'])
			->setGroup(['ENTITY_ID', 'ENTITY_TYPE_ID'])
			->exec()
		;
		while($item = $iterator->fetch())
		{
			$lastEntityTypeId = (int)$item['ENTITY_TYPE_ID'];
			$lastEntityId = (int)$item['ENTITY_ID'];
			$dataByEntityTypeId[$lastEntityTypeId][] = $lastEntityId;
		}

		$iterator = EntityCountableActivityTable::query()
			->setSelect([
				'ENTITY_ID',
				'ENTITY_TYPE_ID',
			])
			->where('ENTITY_ID', '>', $lastEntityId)
			->setLimit($limit)
			->setOrder(['ENTITY_ID' => 'ASC', 'ENTITY_TYPE_ID' => 'ASC'])
			->setGroup(['ENTITY_ID', 'ENTITY_TYPE_ID'])
			->exec()
		;
		while($item = $iterator->fetch())
		{
			$lastEntityTypeId = (int)$item['ENTITY_TYPE_ID'];
			$lastEntityId = (int)$item['ENTITY_ID'];
			$dataByEntityTypeId[$lastEntityTypeId][] = $lastEntityId;
		}

		if (empty($dataByEntityTypeId))
		{
			return [];
		}

		$result = [];
		foreach ($dataByEntityTypeId as $entityTypeId => $entityIds)
		{
			$entityIds = array_unique($entityIds);
			sort($entityIds);
			if (empty($entityIds))
			{
				continue;
			}

			$factory = Container::getInstance()->getFactory($entityTypeId);
			if (!$factory)
			{
				continue;
			}

			$assignedByFiledName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
			$select = [
				Item::FIELD_NAME_ID,
			];
			if ($factory->isFieldExists(Item::FIELD_NAME_ASSIGNED))
			{
				$select[] = $assignedByFiledName;
			}
			$entitiesIterator = $factory->getDataClass()::query()
				->setSelect($select)
				->whereIn('ID', $entityIds)
				->exec();

			$entityData = [];
			while ($entity = $entitiesIterator->fetch())
			{
				$entityData[$entity[Item::FIELD_NAME_ID]] = [
					'DELETED' => false,
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $entity[Item::FIELD_NAME_ID],
					'ENTITY_ASSIGNED_BY_ID' => $entity[$assignedByFiledName],
				];
			}

			foreach ($entityIds as $entityId)
			{
				if (isset($entityData[$entityId]))
				{
					$result[] = $entityData[$entityId];
				}
				else
				{
					$result[] = [
						'DELETED' => true,
						'ENTITY_TYPE_ID' => $entityTypeId,
						'ENTITY_ID' => $entityId,
					];
				}
			}
		}

		return $result;
	}

	private function synchronizeActivityData(array $activity): void
	{
		$activityId = (int)$activity['ID'];
		$countableActIterator = EntityCountableActivityTable::query()
			->setSelect([
				'ACTIVITY_ID',
				'ACTIVITY_RESPONSIBLE_ID',
				'ACTIVITY_DEADLINE',
				'ACTIVITY_IS_INCOMING_CHANNEL',
			])
			->where('ACTIVITY_ID', $activityId)
			->setLimit($this->getLinkedItemsLimit())
			->setOrder(['ID' => 'ASC'])
			->exec()
		;
		while ($countable = $countableActIterator->fetch())
		{
			$deadlineDiffers =
				!$countable['ACTIVITY_DEADLINE'] instanceof DateTime
				|| !$activity['DEADLINE']  instanceof DateTime
				|| ($countable['ACTIVITY_DEADLINE']->getTimestamp() !== $activity['DEADLINE']->getTimestamp())
			;
			if (
				(int)$countable['ACTIVITY_RESPONSIBLE_ID'] !== (int)$activity['RESPONSIBLE_ID']
				|| $countable['ACTIVITY_IS_INCOMING_CHANNEL'] !== $activity['IS_INCOMING_CHANNEL']
				|| $deadlineDiffers
			)
			{
				\Bitrix\Crm\Counter\Monitor\CountableActivitySynchronizer::synchronizeByActivityId($activityId);
				$bindings = \CCrmActivity::GetBindings($activityId);
				foreach ($bindings as $binding)
				{
					$ownerTypeId = (int)$binding['OWNER_TYPE_ID'];
					$ownerId = (int)$binding['OWNER_ID'];
					$this->resetCounterByEntity($ownerTypeId, $ownerId);
				}
				break;
			}
		}
	}

	private function synchronizeEntityData(array $entity)
	{
		$entityTypeId = (int)$entity['ENTITY_TYPE_ID'];
		$entityId = (int)$entity['ENTITY_ID'];
		$countable = EntityCountableActivityTable::query()
			->setSelect([
				'ENTITY_ID',
				'ENTITY_ASSIGNED_BY_ID',
			])
			->where('ENTITY_ID', $entityId)
			->where('ENTITY_TYPE_ID', $entityTypeId)
			->whereNot('ENTITY_ASSIGNED_BY_ID',  (int)$entity['ENTITY_ASSIGNED_BY_ID'])
			->setLimit(1)
			->fetch()
		;
		if ($countable)
		{
			EntityCountableActivityTable::updateEntityAssignedBy(new ItemIdentifier($entityTypeId, $entityId), (int)$entity['ENTITY_ASSIGNED_BY_ID']);
			$this->resetCounterByEntity($entityTypeId, $entityId, $countable['ENTITY_ASSIGNED_BY_ID']);
		}
	}

	private function resetCounterByEntity($entityTypeId,  $entityId, ?int $oldResponsibleId = null): void
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
		$extras = [];
		$responsibleId = null;
		if ($entity)
		{
			if ($factory->isCategoriesEnabled())
			{
				$extras['CATEGORY_ID'] = $entity[$categoryIdFieldName];
			}
			$responsibleId = $entity[$assignedByFiledName];
		}

		$counterCodes = EntityCounterManager::prepareCodes(
			$entityTypeId,
			EntityCounterType::getAll(true),
			$extras
		);
		$users = [];
		if ($oldResponsibleId)
		{
			$users[] = $oldResponsibleId;
		}
		if ($responsibleId)
		{
			$users[] = $responsibleId;
		}

		if(!empty($counterCodes))
		{
			EntityCounterManager::reset($counterCodes, $users);
			EntityCounterManager::resetExcludeUsersCounters($counterCodes, $users);
		}
	}

	private function isEntityCountableDataAgentExists(): bool
	{
		$agent = \CAgent::GetList(
			['ID' => 'DESC'],
			[
				'MODULE_ID' => 'crm',
				'NAME' => '\Bitrix\Crm\Agent\Activity\SynchronizeEntityCountableDataAgent::execAgent();',
			],
		)->Fetch();

		return (bool)$agent;
	}

	private function processCountableActivities(array $result): array
	{
		$limit = $this->getLimit();
		$lastId = ($result['lastEntityCountableActId'] ?? 0);
		$processedCount = 0;

		$activities = $this->getCountableActivityList($lastId, $limit);
		foreach ($activities as $activity)
		{
			$lastId = (int)$activity['ID'];
			$result['steps']++;
			$processedCount++;

			$activityId = (int)$activity['ID'];
			if ($activity['COMPLETED'] || $activity['DELETED'])
			{
				EntityCountableActivityTable::deleteByActivity($activityId);

				$bindings = \CCrmActivity::GetBindings($activityId);
				foreach ($bindings as $binding)
				{
					$ownerTypeId = (int)$binding['OWNER_TYPE_ID'];
					$ownerId = (int)$binding['OWNER_ID'];
					$this->resetCounterByEntity($ownerTypeId, $ownerId);
				}
			}
			else
			{
				$this->synchronizeActivityData($activity);
			}
		}

		$result['lastEntityCountableActId'] = $lastId;

		if ($processedCount < $limit)
		{
			$result['currentStep'] = self::STEP_COUNTABLE_ENTITIES;
		}

		return $result;
	}

	private function processCountableEntities(array $result): array
	{
		$limit = $this->getLimit();
		$lastEntityId = ($result['lastEntityCountableEntityId'] ?? 0);
		$lastEntityTypeId = ($result['lastEntityCountableEntityTypeId'] ?? 0);
		$processedCount = 0;

		$entities = $this->getCountableEntityList($lastEntityTypeId, $lastEntityId, $limit);
		foreach ($entities as $entity)
		{
			$entityId = (int)$entity['ENTITY_ID'];
			$entityTypeId = (int)$entity['ENTITY_TYPE_ID'];
			$result['steps']++;
			$processedCount++;

			if ($entity['DELETED'])
			{
				if (\CCrmOwnerType::isCorrectEntityTypeId($entityTypeId) && $entityId > 0)
				{
					EntityCountableActivityTable::deleteByEntity(new ItemIdentifier($entityTypeId, $entityId));
					$this->resetCounterByEntity($entityTypeId, $entityId);
				}
			}
			else
			{
				$this->synchronizeEntityData($entity);
			}
		}

		$result['lastEntityCountableEntityId'] = $lastEntityId;
		$result['lastEntityCountableEntityTypeId'] = $lastEntityTypeId;

		if ($processedCount < $limit)
		{
			$result['currentStep'] = self::STEP_UNCOMPLETED_ACTIVITIES;
		}

		return $result;
	}


	private function getNextEntityTypeId(int $entityTypeId): ? int
	{
		$data = EntityCountableActivityTable::query()
			->setSelect([
				'ENTITY_TYPE_ID',
			])
			->where('ENTITY_TYPE_ID', '>', $entityTypeId)
			->setOrder(['ENTITY_TYPE_ID' => 'ASC'])
			->setGroup(['ENTITY_TYPE_ID'])
			->fetch()
		;
		if ($data)
		{
			return (int)$data['ENTITY_TYPE_ID'];
		}

		return null;
	}

	private function processEntityUncompletedActivities(array $result): array
	{
		$limit = $this->getLimit();
		$activityId = ($result['lastActId'] ?? 0);
		$processedCount = 0;

		$items = $this->getUncompletedActivitiesList($activityId, $limit);

		$activityIds = [];
		foreach ($items as $item)
		{
			$activityId = (int)$item['ID'];
			$result['steps']++;
			$processedCount++;

			if (($item['DEADLINE'] && !\CCrmDateTimeHelper::IsMaxDatabaseDate($item['DEADLINE'])) || $item['IS_INCOMING_CHANNEL'] == 'Y')
			{
				$activityIds[] = $activityId;
			}
		}

		$activitiesToSynchronize = [];
		if (!empty($activityIds))
		{
			$iterator = ActivityBindingTable::query()
				->setSelect([
					'OWNER_ID',
					'OWNER_TYPE_ID',
					'ACTIVITY_ID',
				])
				->whereIn('ACTIVITY_ID', $activityIds)
				->registerRuntimeField(
					'',
					new ReferenceField('A',
						EntityCountableActivityTable::getEntity(),
						(new ConditionTree())
							->whereColumn('ref.ACTIVITY_ID', 'this.ACTIVITY_ID')
							->whereColumn('ref.ENTITY_TYPE_ID', 'this.OWNER_TYPE_ID')
							->whereColumn('ref.ENTITY_ID', 'this.OWNER_ID'),
						['join_type' => Join::TYPE_LEFT]
					)
				)
				->whereNull('A.ENTITY_ID')
				->exec()
			;
			while ($item = $iterator->fetch())
			{
				$ownerTypeId = (int)$item['OWNER_TYPE_ID'];
				$ownerId = (int)$item['OWNER_ID'];
				$activityId = (int)$item['ACTIVITY_ID'];
				$this->resetCounterByEntity($ownerTypeId, $ownerId);
				$activitiesToSynchronize[] = $activityId;
			}
			$activitiesToSynchronize = array_unique($activitiesToSynchronize);

			foreach ($activitiesToSynchronize as $activityId)
			{
				\Bitrix\Crm\Counter\Monitor\CountableActivitySynchronizer::synchronizeByActivityId($activityId);
			}
		}

		$result['lastActId'] = $activityId;

		if ($processedCount < $limit)
		{
			$result['currentStep'] = '-';
		}

		return $result;
	}

	private function getUncompletedActivitiesList(int $lastId, int $limit): array
	{
		$ids = array_column(ActivityTable::query()
			->setSelect([
				'ID',
			])
			->where('ID', '>', $lastId)
			->where('COMPLETED', false)
			->setLimit($limit)
			->setOrder(['ID' => 'ASC'])
			->fetchAll(), 'ID')
		;
		if (empty($ids))
		{
			return [];
		}

		$items = ActivityTable::query()
			->setSelect([
				'ID',
				'DEADLINE',
			])
			->whereIn('ID', $ids)
			->fetchAll()
		;
		$incomingChannelActivityIds = array_column(
			IncomingChannelTable::query()
				->setSelect([
					'ACTIVITY_ID',
				])
				->whereIn('ACTIVITY_ID', $ids)
				->fetchAll(),
			'ACTIVITY_ID'
		);
		foreach ($items as &$item)
		{
			$item['IS_INCOMING_CHANNEL'] = in_array($item['ID'], $incomingChannelActivityIds, false) ? 'Y' : 'N';
		}

		return $items;
	}
}

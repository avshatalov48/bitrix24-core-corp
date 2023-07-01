<?php

namespace Bitrix\Crm\Counter\Monitor;

use Bitrix\Crm\Activity\IncomingChannel;
use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeRepo;
use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeTable;
use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Type\DateTime;

class CountableActivitySynchronizer
{
	public static function synchronizeByEntityChange(EntityChange $entityChange): void
	{
		if ($entityChange->wasEntityDeleted())
		{
			EntityCountableActivityTable::deleteByEntity($entityChange->getIdentifier());
		}
		elseif(!$entityChange->wasEntityAdded() && $entityChange->isAssignedByChanged())
		{
			EntityCountableActivityTable::updateEntityAssignedBy($entityChange->getIdentifier(), (int)$entityChange->getNewAssignedById());
		}
	}

	public static function synchronizeByActivityChange(ActivityChange $activityChange, array $entitiesData): void
	{
		if ($activityChange->wasActivityDeleted())
		{
			EntityCountableActivityTable::deleteByActivity($activityChange->getId());

			return;
		}
		$canOldRecordExists =
			(!is_null($activityChange->getOldDeadline()) || $activityChange->getOldIsIncomingChannel())
			&& !$activityChange->getOldIsCompleted()
		;

		// if activity was completed:
		if ($canOldRecordExists && $activityChange->getNewIsCompleted())
		{
			EntityCountableActivityTable::deleteByActivity($activityChange->getId());
		}
		// if deadline and incoming flag was removed:
		elseif($canOldRecordExists && is_null($activityChange->getNewDeadline()) && !$activityChange->getNewIsIncomingChannel())
		{
			EntityCountableActivityTable::deleteByActivity($activityChange->getId());
		}
		// if activity has deadline or is incoming and is not completed:
		elseif (
			(!is_null($activityChange->getNewDeadline()) || $activityChange->getNewIsIncomingChannel())
			&& !$activityChange->getNewIsCompleted()
		)
		{

			/** @var ActCounterLightTimeRepo $lightCounterRepo */
			$lightCounterRepo = ServiceLocator::getInstance()->get('crm.activity.actcounterlighttimerepo');
			$lightCounterAt = $lightCounterRepo->queryLightTimeByActivityId($activityChange->getId());

			$deadline = $activityChange->getNewDeadline() ?? \CCrmDateTimeHelper::getMaxDatabaseDateObject();

			if (!$lightCounterAt)
			{
				$lightCounterAt = clone $deadline;
				$lightCounterAt->add('-PT15M');
			}

			foreach ($activityChange->getNewBindings() as $binding)
			{
				if (!\CCrmOwnerType::IsEntity($binding->getEntityTypeId()))
				{
					continue;
				}

				$entityData = $entitiesData[$binding->getEntityTypeId()][$binding->getEntityId()] ?? [];
				EntityCountableActivityTable::upsert([
					'ENTITY_TYPE_ID' => $binding->getEntityTypeId(),
					'ENTITY_ID' => $binding->getEntityId(),
					'ENTITY_ASSIGNED_BY_ID' => $entityData['assignedBy'] ?? 0,
					'ACTIVITY_ID' => $activityChange->getId(),
					'ACTIVITY_RESPONSIBLE_ID' => (int)$activityChange->getNewResponsibleId(),
					'ACTIVITY_DEADLINE' => $activityChange->getNewDeadline() ?? \CCrmDateTimeHelper::getMaxDatabaseDateObject(),
					'ACTIVITY_IS_INCOMING_CHANNEL' => (bool)$activityChange->getNewIsIncomingChannel(),
					'LIGHT_COUNTER_AT' => $lightCounterAt,
					'DEADLINE_EXPIRED_AT' => self::endDayOfDateTime($deadline)
				]);
			}
		}
	}

	public static function synchronizeByActivityId(int $activityId): void
	{
		$activity = \CCrmActivity::GetByID($activityId, false);
		if (!$activity)
		{
			return;
		}
		EntityCountableActivityTable::deleteByActivity($activityId);

		self::synchronizeByActivity($activity);
	}

	public static function initialSynchronizeByActivityId(int $activityId): void
	{
		$existedItem = EntityCountableActivityTable::query()
			->where('ACTIVITY_ID', $activityId)
			->setSelect(['ID'])
			->setLimit(1)
			->fetch()
		;

		if ($existedItem)
		{
			return; // already synchronized
		}

		$activity = \CCrmActivity::GetByID($activityId, false);
		if (!$activity)
		{
			return;
		}

		self::synchronizeByActivity($activity);
	}

	private static function synchronizeByActivity(array $activity): void
	{
		$activityId = (int)$activity['ID'];
		$activity['IS_INCOMING_CHANNEL'] = IncomingChannel::getInstance()->isIncomingChannel($activityId) ? 'Y' : 'N';

		$bindings = \CCrmActivity::GetBindings($activityId);
		$deadline = ($activity['DEADLINE'] && !\CCrmDateTimeHelper::IsMaxDatabaseDate($activity['DEADLINE']))
			? DateTime::createFromUserTime($activity['DEADLINE'])
			: null
		;
		$responsibleId = (int)$activity['RESPONSIBLE_ID'];
		$isCompleted = ($activity['COMPLETED'] === 'Y');
		$isIncomingChannel = ($activity['IS_INCOMING_CHANNEL'] === 'Y');

		if ($isCompleted || (!$deadline && !$isIncomingChannel))
		{
			return;
		}
		$processedBindings = [];


		/** @var ActCounterLightTimeRepo $lightCounterRepo */
		$lightCounterRepo = ServiceLocator::getInstance()->get('crm.activity.actcounterlighttimerepo');
		$lightCounterAt = $lightCounterRepo->queryLightTimeByActivityId($activityId);

		if (!$lightCounterAt && $deadline)
		{
			$lightCounterAt = clone $deadline;
			$lightCounterAt->add('-PT15M');
		}

		foreach ($bindings as $binding)
		{
			$entityTypeId = (int)$binding['OWNER_TYPE_ID'];
			if (!\CCrmOwnerType::IsEntity($entityTypeId))
			{
				continue;
			}
			$entityId = (int)$binding['OWNER_ID'];
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if (!$factory)
			{
				continue;
			}
			$key = $entityTypeId . '-' . $entityId;
			if ($processedBindings[$key])
			{
				continue;
			}
			$processedBindings[$key] = true;

			$assignedByFiledName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
			$entityAssignedById = $factory->getDataClass()::query()
				->setSelect([Item::FIELD_NAME_ID, $assignedByFiledName])
				->where('ID', $entityId)
				->fetch()[$assignedByFiledName] ?? 0;
			;

			$fields = [
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'ENTITY_ASSIGNED_BY_ID' => $entityAssignedById,
				'ACTIVITY_ID' => $activityId,
				'ACTIVITY_RESPONSIBLE_ID' => $responsibleId,
				'ACTIVITY_DEADLINE' => $deadline ?? \CCrmDateTimeHelper::getMaxDatabaseDateObject(),
				'ACTIVITY_IS_INCOMING_CHANNEL' => $isIncomingChannel,
				'LIGHT_COUNTER_AT' => $lightCounterAt ?? \CCrmDateTimeHelper::getMaxDatabaseDateObject(),
				'DEADLINE_EXPIRED_AT' => self::endDayOfDateTime($deadline) ?? \CCrmDateTimeHelper::getMaxDatabaseDateObject()
			];

			try
			{
				EntityCountableActivityTable::add($fields);
			}
			catch (SqlQueryException $e)
			{
				if (mb_strpos($e->getMessage(), 'Duplicate entry') !== false)
				{
					EntityCountableActivityTable::upsert($fields);
				}
			}
		}
	}

	private static function endDayOfDateTime(?DateTime $dateTime): ?DateTime
	{
		if ($dateTime === null)
		{
			return null;
		}
		$dt = clone $dateTime;
		$dt->setTime(23, 59, 59);
		return $dt;
	}
}

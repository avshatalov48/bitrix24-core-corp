<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeRepo;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Timeline\Monitor;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\DateTime;

class UncompletedActivity
{
	private bool $existedRecordLoaded = false;
	private ?array $existedRecord = null;
	private ItemIdentifier $itemIdentifier;
	private int $responsibleId;
	private ?UncompletedActivityChange $activityChange = null;
	private ActCounterLightTimeRepo $lightTimeRepo;

	public static function synchronizeForUncompletedActivityChange(UncompletedActivityChange $change): void
	{
		if ($change->isChangedAlreadyCompletedActivity())
		{
			return;
		}

		$bindings = [];
		$removedBindings = [];
		foreach ($change->getNewBindings() as $binding)
		{
			$bindings[$binding->getHash()] = $binding;
		}
		foreach ($change->getOldBindings() as $binding)
		{
			if (!array_key_exists($binding->getHash(), $bindings))
			{
				$removedBindings[$binding->getHash()] = $binding;
			}
		}

		foreach ($bindings as $binding)
		{
			$affectedUserIds = [0];
			if ($change->getNewResponsibleId())
			{
				$affectedUserIds[] = $change->getNewResponsibleId();
			}
			if ($change->isResponsibleIdChanged() && $change->getOldResponsibleId())
			{
				$affectedUserIds[] = $change->getOldResponsibleId();
			}

			foreach ($affectedUserIds as $responsibleId)
			{
				$instance = new self($binding, (int)$responsibleId);
				$instance->setActivityChange($change);
				$instance->synchronize();
			}
		}

		if (!empty($removedBindings))
		{
			$changeForRemovedBindings = new UncompletedActivityChange(
				$change->getId(),
				$change->getOldIsIncomingChannel(),
				null,
				$change->getOldDeadline(),
				null,
				$change->getOldResponsibleId(),
				null,
				$change->getOldIsCompleted(),
				null,
				$removedBindings,
				$removedBindings,
				$change->getOldLightTime(),
				null
			);
			foreach ($removedBindings as $binding)
			{
				$affectedUserIds = [0];
				if ($change->getOldResponsibleId())
				{
					$affectedUserIds[] = $change->getOldResponsibleId();
				}
				if ($change->getNewResponsibleId())
				{
					$affectedUserIds[] = $change->getNewResponsibleId();
				}
				$affectedUserIds = array_unique($affectedUserIds);
				foreach ($affectedUserIds as $responsibleId)
				{
					$instance = new self($binding, (int)$responsibleId);
					$instance->setActivityChange($changeForRemovedBindings);
					$instance->synchronize();
				}
			}
		}
	}

	public static function synchronizeForBindingsAndResponsibles(array $bindings, array $responsibleIds): void
	{
		$responsibleIds = array_unique($responsibleIds);

		$processedBindingsMap = [];
		foreach ($bindings as $binding)
		{
			if (!\CCrmOwnerType::isCorrectEntityTypeId($binding['OWNER_TYPE_ID']))
			{
				continue;
			}
			if ($binding['OWNER_ID'] <= 0)
			{
				continue;
			}
			if (isset($processedBindingsMap[$binding['OWNER_TYPE_ID']][$binding['OWNER_ID']]))
			{
				continue;
			}
			$processedBindingsMap[$binding['OWNER_TYPE_ID']][$binding['OWNER_ID']] = true;

			foreach ($responsibleIds as $responsibleId)
			{
				$instance = new self(new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']), (int)$responsibleId);
				$instance->synchronize();
			}
		}
	}

	public static function synchronizeForActivity(int $activityId, ?array $bindings = null): void
	{
		$activity = \CCrmActivity::GetList(
			[],
			['=ID' => $activityId, 'CHECK_PERMISSIONS' => 'N'],
			false,
			['nTopCount' => 1],
			['RESPONSIBLE_ID', 'COMPLETED']
		)->Fetch();

		if ($activity && $activity['COMPLETED'] === 'N')
		{
			if (is_null($bindings))
			{
				$bindings = \CCrmActivity::GetBindings($activityId);
			}
			self::synchronizeForBindingsAndResponsibles(
				$bindings,
				[
					0,
					(int)$activity['RESPONSIBLE_ID']
				]
			);
		}
	}

	public function __construct(ItemIdentifier $itemIdentifier, int $responsibleId)
	{
		$this->lightTimeRepo = new ActCounterLightTimeRepo();

		$this->itemIdentifier = $itemIdentifier;
		$this->responsibleId = $responsibleId;
	}

	public function getActivityChange(): ?UncompletedActivityChange
	{
		return $this->activityChange;
	}

	public function setActivityChange(?UncompletedActivityChange $activityChange): self
	{
		$this->activityChange = $activityChange;

		return $this;
	}

	public function synchronize(): void
	{
		if ($this->trySynchronizeByActivityChange())
		{
			Monitor::getInstance()->onUncompletedActivityChange($this->itemIdentifier);

			return;
		}

		$uncompletedActivity = $this->getUncompletedActivity();

		$isChanged = false;
		if ($uncompletedActivity)
		{
			$activityId = (int)$uncompletedActivity['ID'];
			$deadline = $uncompletedActivity['DEADLINE'] ?? \CCrmDateTimeHelper::GetMaxDatabaseDate(false);
			$isIncomingChannel = false;
			$incomingChannelActivityId = $this->getUncompletedIncomingActivityId();
			$hasAnyIncomingChannel = !!$incomingChannelActivityId;
			if (\CCrmDateTimeHelper::IsMaxDatabaseDate($deadline))
			{
				if ($incomingChannelActivityId)
				{
					$activityId = $incomingChannelActivityId;
					$isIncomingChannel = true;
				}
				$deadlineDateTime = \CCrmDateTimeHelper::getMaxDatabaseDateObject();
			}
			else
			{
				$deadlineDateTime = DateTime::createFromUserTime($deadline);
			}

			$minLightTime = $this->lightTimeRepo->minLightTimeByItemIdentifier($this->itemIdentifier);

			$this->upsert(
				$activityId,
				$deadlineDateTime,
				$isIncomingChannel,
				$hasAnyIncomingChannel,
				$minLightTime
			);
			$isChanged = true;
		}
		else
		{
			// there are no uncompleted activities
			$existedRecordId = $this->getExistedRecordId();
			if ($existedRecordId)
			{
				EntityUncompletedActivityTable::delete($existedRecordId);
				$isChanged = true;
			}
		}

		if ($isChanged)
		{
			Monitor::getInstance()->onUncompletedActivityChange($this->itemIdentifier);
		}
	}

	private function getUncompletedActivity(): ?array
	{
		$filter = $this->prepareUncompletedActivityFilter();

		$explicitDeadlineFilter = $filter;
		$explicitDeadlineFilter['!=DEADLINE'] = null;

		$firstUncompletedActivityWithExplicitDeadline = \CCrmActivity::GetList(
			[
				'DEADLINE' => 'ASC',
			],
			$explicitDeadlineFilter,
			false,
			['nTopCount' => 1],
			[
				'ID',
				'DEADLINE'
			]
		)->Fetch();

		if ($firstUncompletedActivityWithExplicitDeadline)
		{
			return $firstUncompletedActivityWithExplicitDeadline;
		}

		/*
		 * at this point there are either activities with DEADLINE === null or no activities at all.
		 * if an activity has DEADLINE === null, it's essentially means that its deadline is \CCrmDateTimeHelper::GetMaxDatabaseDate().
		 * so simply fetch the most recent activity, if it exists. DEADLINE for all of them is going be the same.
		 */
		$lastUncompletedActivity = \CCrmActivity::GetList(
			[
				'CREATED' => 'DESC',
			],
			$filter,
			false,
			['nTopCount' => 1],
			[
				'ID',
				'DEADLINE'
			]
		)->Fetch();

		return $lastUncompletedActivity ? $lastUncompletedActivity : null;
	}

	private function prepareUncompletedActivityFilter(): array
	{
		$filter = [
			'BINDINGS' => [
				[
					'OWNER_TYPE_ID' => $this->itemIdentifier->getEntityTypeId(),
					'OWNER_ID' => $this->itemIdentifier->getEntityId(),
				],
			],
			'CHECK_PERMISSIONS' => 'N',
			'COMPLETED' => 'N',
		];
		if ($this->responsibleId > 0)
		{
			$filter['RESPONSIBLE_ID'] = $this->responsibleId;
		}

		return $filter;
	}

	protected function upsert(
		int $activityId,
		DateTime $minDeadline,
		bool $isIncomingChannel,
		bool $hasAnyIncomingChannel,
		DateTime $minLightTime
	): void
	{
		$fields = [
			'ACTIVITY_ID' => $activityId,
			'MIN_DEADLINE' => $minDeadline,
			'IS_INCOMING_CHANNEL' => $isIncomingChannel ? 'Y' : 'N',
			'HAS_ANY_INCOMING_CHANEL' => $hasAnyIncomingChannel ? 'Y' : 'N',
			'MIN_LIGHT_COUNTER_AT' => $minLightTime
		];
		$existedRecord = $this->loadExistedRecord();

		if ($existedRecord)
		{
			try
			{
				EntityUncompletedActivityTable::update((int)$existedRecord['ID'], $fields);
			}
			catch (SqlQueryException $e)
			{
				if (mb_strpos($e->getMessage(), 'Duplicate entry') !== false)
				{
					$existedMoreActualRecord = EntityUncompletedActivityTable::query()
						->where('ENTITY_TYPE_ID', $this->itemIdentifier->getEntityTypeId())
						->where('ENTITY_ID', $this->itemIdentifier->getEntityId())
						->where('RESPONSIBLE_ID', $this->responsibleId)
						->where('MIN_DEADLINE', $fields['MIN_DEADLINE'])
						->setLimit(1)
						->setSelect(['ID'])
						->fetch()
					;
					if (!$existedMoreActualRecord || (int)$existedMoreActualRecord['ID'] === (int)$existedRecord['ID'])
					{
						throw $e;
					}
					EntityUncompletedActivityTable::update((int)$existedMoreActualRecord['ID'], $fields);
					EntityUncompletedActivityTable::delete((int)$existedRecord['ID']);
				}
			}
		}
		else
		{
			$addFields = $fields;
			$addFields['ENTITY_TYPE_ID'] = $this->itemIdentifier->getEntityTypeId();
			$addFields['ENTITY_ID'] = $this->itemIdentifier->getEntityId();
			$addFields['RESPONSIBLE_ID'] = $this->responsibleId;
			$addFields['MIN_LIGHT_COUNTER_AT'] = $minLightTime;

			try
			{
				EntityUncompletedActivityTable::add($addFields);
			}
			catch (SqlQueryException $e)
			{
				if (mb_strpos($e->getMessage(), 'Duplicate entry') !== false)
				{
					$existedRecord = $this->loadExistedRecord();
					if (!$existedRecord)
					{
						throw $e;
					}
					EntityUncompletedActivityTable::update($existedRecord['ID'], $fields);
				}
			}
		}
	}

	public static function unregister(ItemIdentifier $itemIdentifier): void
	{
		EntityUncompletedActivityTable::deleteByItemIdentifier($itemIdentifier);
	}

	private function getExistedRecordId(): ?int
	{
		$existedRecord = $this->getExistedRecord();

		return (isset($existedRecord['ID']) && $existedRecord['ID']) ? (int)$existedRecord['ID'] : null;
	}

	protected function getExistedRecord(): ?array
	{
		if ($this->existedRecordLoaded)
		{
			return $this->existedRecord;
		}
		$this->existedRecordLoaded = true;
		$existedRecord = $this->loadExistedRecord();
		$this->existedRecord = is_array($existedRecord) ? $existedRecord : null;

		return $this->existedRecord;
	}

	private function loadExistedRecord(): ?array
	{
		$existedRecord = EntityUncompletedActivityTable::query()
			->where('ENTITY_TYPE_ID', $this->itemIdentifier->getEntityTypeId())
			->where('ENTITY_ID', $this->itemIdentifier->getEntityId())
			->where('RESPONSIBLE_ID', $this->responsibleId)
			->setSelect(['ID', 'MIN_DEADLINE', 'IS_INCOMING_CHANNEL', 'HAS_ANY_INCOMING_CHANEL', 'MIN_LIGHT_COUNTER_AT'])
			->setLimit(1)
			->fetch()
		;

		return is_array($existedRecord) ? $existedRecord : null;
	}

	private function trySynchronizeByActivityChange(): bool
	{
		if ($this->activityChange === null)
		{
			return false;
		}
		if (!$this->activityChange->hasChanges())
		{
			return true;
		}
		if ($this->uncompletedActivityTableHasInconsistentData())
		{
			return false;
		}

		if (
			!$this->activityChange->wasActivityJustDeleted()
			&& $this->responsibleId > 0
			&& $this->activityChange->isResponsibleIdChanged()
			&& $this->activityChange->getOldResponsibleId() === $this->responsibleId)
		{
			// for old responsible activityChange can not be used
			return false;
		}

		$existedRecord = $this->getExistedRecord();
		if (!$existedRecord) // activityChange is about first activity
		{
			if (!$this->activityChange->wasActivityJustDeleted() && !$this->activityChange->getNewIsCompleted())
			{
				$this->updateByActivityChange();
			}

			return true;
		}

		$existedDeadline = $existedRecord['MIN_DEADLINE'] && \CCrmDateTimeHelper::IsMaxDatabaseDate($existedRecord['MIN_DEADLINE']->toString())
			? null
			: $existedRecord['MIN_DEADLINE']
		;
		$existedIsIncomingChannel = ($existedRecord['IS_INCOMING_CHANNEL'] === 'Y');
		$existedAnyIncomingChannel = ($existedRecord['HAS_ANY_INCOMING_CHANEL'] === 'Y');

		if ($this->activityChange->wasActivityJustDeleted() || $this->activityChange->wasActivityJustCompleted())
		{
			// check if deleted(completed) activity can affect the values in $existedRecord:
			$deletedActivityDeadline = $this->activityChange->getOldDeadline();
			$deletedActivityIsIncomingChannel = $this->activityChange->getOldIsIncomingChannel();

			if ($existedDeadline &&
				(
					($deletedActivityDeadline && $existedDeadline->getTimestamp() < $deletedActivityDeadline->getTimestamp())
					|| (!$deletedActivityDeadline && !$deletedActivityIsIncomingChannel)
				)
			)
			{
				return true; // deleted activity doesn't affect
			}
			if (!$existedDeadline && $existedIsIncomingChannel && !$deletedActivityIsIncomingChannel)
			{
				return true; // deleted activity doesn't affect
			}

			return false;
		}
		else
		{
			$newDeadline = $this->activityChange->getNewDeadline();
			$newIsIncomingChannel = $this->activityChange->getNewIsIncomingChannel();

			if ($this->activityChange->wasActivityJustAdded() || $this->activityChange->wasActivityJustUnCompleted())
			{
				if ($this->activityChange->getNewIsCompleted()) // new completed activity doesn't affect
				{
					return true;
				}
				if (!$existedDeadline && !$newDeadline)
				{
					if (
						$existedIsIncomingChannel
						|| (!$existedIsIncomingChannel && !$newIsIncomingChannel)
					)
					{
						return true; // new(uncompleted) activity doesn't affect
					}
					// (!$existedIsIncomingChannel && $newIsIncomingChannel):
					$this->updateByActivityChange(); // update IS_INCOMING_CHANNEL

					return true;
				}
				if (!$newDeadline)
				{
					if (!$existedAnyIncomingChannel && $newIsIncomingChannel)
					{
						return false; // need update HAS_ANY_INCOMING_CHANEL only
					}

					return true; //  new(uncompleted) activity doesn't affect because $existedDeadline has more privileges
				}
				if (!$existedDeadline)
				{
					$this->updateByActivityChange(); // set MIN_DEADLINE

					return true;
				}
				// $existedDeadline && $newDeadline
				if ($existedDeadline->getTimestamp() <= $newDeadline->getTimestamp())
				{
					return true; // $newDeadline is greater than $existedDeadline, so doesn't affect
				}
				$this->updateByActivityChange(); // update MIN_DEADLINE

				return true;
			}
			else // activity fields was updated
			{
				/** @var DateTime $existedLightTime */
				$existedLightTime = $existedRecord['MIN_LIGHT_COUNTER_AT'] ?? null;
				$activityLightTime = $this->activityChange->getNewLightTime();

				if (
					!$existedLightTime
					|| !$activityLightTime
					|| $existedLightTime->getTimestamp() > $activityLightTime->getTimestamp()
				)
				{
					$this->updateByActivityChange();
					return true;
				}

				$oldDeadline = $this->activityChange->getOldDeadline();
				if ($existedDeadline && $newDeadline && $oldDeadline)
				{
					if (
						$existedDeadline->getTimestamp() < $oldDeadline->getTimestamp()
						&& $existedDeadline->getTimestamp() < $newDeadline->getTimestamp()
					)
					{
						return true; // deadline change doesn't affect
					}
				}

				return false;
			}
		}

		return false;
	}

	public function updateByActivityChange(): void
	{
		$isIncomingChannel = false;
		$deadline = $this->activityChange->getNewDeadline();
		if (!$deadline)
		{
			$deadline = \CCrmDateTimeHelper::getMaxDatabaseDateObject();
			$isIncomingChannel = ($this->activityChange->getNewIsIncomingChannel() === true);
		}
		$hasAnyIncomingChannel = $isIncomingChannel;
		if (!$isIncomingChannel)
		{
			$hasAnyIncomingChannel = !!$this->getUncompletedIncomingActivityId();
		}

		$minLightTime = $this->lightTimeRepo->minLightTimeByItemIdentifier($this->itemIdentifier);

		$this->upsert(
			$this->activityChange->getId(),
			$deadline,
			$isIncomingChannel,
			$hasAnyIncomingChannel,
			$minLightTime
		);
	}

	private function getUncompletedIncomingActivityId(): ?int
	{
		$query = IncomingChannelTable::query()
			->setSelect(['ACTIVITY_ID'])
			->where('BINDINGS.OWNER_TYPE_ID', $this->itemIdentifier->getEntityTypeId())
			->where('BINDINGS.OWNER_ID', $this->itemIdentifier->getEntityId())
			->where('COMPLETED', false)
			->setLimit(1)
		;
		if ($this->responsibleId > 0)
		{
			$query->where('RESPONSIBLE_ID', $this->responsibleId);
		}
		$activity = $query->fetch();

		return $activity ? (int)$activity['ACTIVITY_ID'] : null;
	}

	private function uncompletedActivityTableHasInconsistentData(): bool
	{
		return \Bitrix\Main\Config\Option::get('crm', 'enable_any_incoming_act', 'Y') === 'N';
	}
}

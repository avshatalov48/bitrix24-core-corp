<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeRepo;
use Bitrix\Crm\Activity\UncompletedActivity\SyncByActivityChange;
use Bitrix\Crm\Activity\UncompletedActivity\UncompletedActivityRepo;
use Bitrix\Crm\Activity\UncompletedActivity\UpsertDto;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Timeline\Monitor;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\DateTime;

class UncompletedActivity
{
	private ItemIdentifier $itemIdentifier;
	private int $responsibleId;
	private ?UncompletedActivityChange $activityChange = null;
	private ActCounterLightTimeRepo $lightTimeRepo;

	private UncompletedActivityRepo $uncompletedActivityRepo;

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
		$this->uncompletedActivityRepo = new UncompletedActivityRepo($itemIdentifier, $responsibleId);

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
			$incomingChannelActivityId = $this->uncompletedActivityRepo->getUncompletedIncomingActivityId();
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

			$responsibleToCalcLightTime = null;
			if (CounterSettings::getInstance()->useActivityResponsible())
			{
				$responsibleToCalcLightTime = $this->responsibleId > 0 ? $this->responsibleId : null;
			}

			$minLightTime = $this->lightTimeRepo->minLightTimeByItemIdentifier(
				$this->itemIdentifier,
				$responsibleToCalcLightTime
			);

			$this->upsert(
				new UpsertDto(
					$activityId,
					$deadlineDateTime,
					$isIncomingChannel,
					$hasAnyIncomingChannel,
					$minLightTime,
					$this->itemIdentifier,
					$this->responsibleId
				)
			);
			$isChanged = true;
		}
		else
		{
			// there are no uncompleted activities
			$existedRecordId = $this->uncompletedActivityRepo->getExistedRecordId();
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

	public function upsert(UpsertDto $dto): void
	{
		$this->uncompletedActivityRepo->upsert($dto);
	}

	public static function unregister(ItemIdentifier $itemIdentifier): void
	{
		EntityUncompletedActivityTable::deleteByItemIdentifier($itemIdentifier);
	}

	private function trySynchronizeByActivityChange(): bool
	{
		$syncByActChange = new SyncByActivityChange(
			$this->itemIdentifier,
			$this->activityChange,
			$this->responsibleId,
			$this->uncompletedActivityRepo
		);

		return $syncByActChange->trySynchronize();
	}




}
